<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\ExtraProperty\QueryHandler;

use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Core\CommandBus\Attributes\AsQueryHandler;
use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\Query\GetExtraPropertyValues;
use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\QueryHandler\GetExtraPropertyValuesHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\QueryResult\ExtraPropertyValuesResult;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyNaming;
use PrestaShop\PrestaShop\Core\ExtraProperty\Registry\EntityExtraFieldRegistryInterface;
use Throwable;

/**
 * Reads extra property values for a single entity instance across all three scopes.
 *
 * Unlike ExtraPropertyReader (which reads one specific lang/shop at a time),
 * this handler loads ALL languages and ALL shops for the entity in one pass.
 * This is the pattern required by the Admin API, which must return values for
 * every registered language and every registered shop in a single response.
 *
 * Lang-scope values are returned indexed by id_lang (int).
 * Shop-scope values are returned indexed by id_shop (int).
 * Callers needing locale strings must convert id_lang → locale themselves.
 *
 * When $query->isDisplayApiOnly() is true, definitions without display_api = 1
 * are skipped so that non-exposed fields are never read from the database.
 */
#[AsQueryHandler]
class GetExtraPropertyValuesHandler implements GetExtraPropertyValuesHandlerInterface
{
    public function __construct(
        protected readonly EntityExtraFieldRegistryInterface $registry,
        protected readonly Connection $connection,
        protected readonly string $prefix,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function handle(GetExtraPropertyValues $query): ExtraPropertyValuesResult
    {
        if ($query->getEntityId() <= 0) {
            return new ExtraPropertyValuesResult([]);
        }

        $allDefinitions = $this->registry->getByEntityNameAllScopes($query->getEntityName());
        if (empty($allDefinitions)) {
            return new ExtraPropertyValuesResult([]);
        }

        // Apply display_api filter when requested
        if ($query->isDisplayApiOnly()) {
            $allDefinitions = array_values(array_filter(
                $allDefinitions,
                static fn (array $def): bool => !empty($def['display_api'])
            ));
        }

        if (empty($allDefinitions)) {
            return new ExtraPropertyValuesResult([]);
        }

        $commonFields = $this->loadCommonScope($query, $allDefinitions);
        $langFields = $this->loadLangScope($query, $allDefinitions);
        $shopFields = $this->loadShopScope($query, $allDefinitions);

        // Deep merge all three partial results into a single module-keyed array
        $result = [];
        foreach ([$commonFields, $langFields, $shopFields] as $partial) {
            foreach ($partial as $moduleName => $fields) {
                foreach ($fields as $fieldName => $value) {
                    $result[$moduleName][$fieldName] = $value;
                }
            }
        }

        return new ExtraPropertyValuesResult($result);
    }

    /**
     * Loads common-scope extra properties (one row per entity from *_extra).
     *
     * @param GetExtraPropertyValues $query
     * @param array<int, array<string, mixed>> $allDefinitions
     *
     * @return array<string, array<string, mixed>> Grouped by module display key; scalar values
     */
    protected function loadCommonScope(GetExtraPropertyValues $query, array $allDefinitions): array
    {
        $columnToPropertyMap = $this->buildColumnPropertyMap($allDefinitions, 'common');
        if (empty($columnToPropertyMap)) {
            return [];
        }

        $extraTableName = $this->prefix . ExtraPropertyNaming::extraTableName($query->getEntityName(), 'common');
        $primaryKeyName = $query->getPrimaryKeyName();

        $selectedColumns = implode(', ', array_map(
            fn (string $col): string => $this->connection->quoteIdentifier($col),
            array_keys($columnToPropertyMap)
        ));

        try {
            $row = $this->connection->fetchAssociative(
                sprintf(
                    'SELECT %s FROM %s WHERE %s = :entityId',
                    $selectedColumns,
                    $this->connection->quoteIdentifier($extraTableName),
                    $this->connection->quoteIdentifier($primaryKeyName)
                ),
                ['entityId' => $query->getEntityId()]
            );
        } catch (Throwable) {
            return [];
        }

        if (false === $row) {
            return [];
        }

        $result = [];
        foreach ($columnToPropertyMap as $columnName => $propertyPath) {
            if (!array_key_exists($columnName, $row)) {
                continue;
            }
            $result[$propertyPath['module_name']][$propertyPath['field_name']] = $row[$columnName];
        }

        return $result;
    }

    /**
     * Loads lang-scope extra properties (all languages) from *_extra_lang.
     *
     * Returns values indexed by id_lang (int) so the caller can apply
     * locale-string conversion if needed (e.g. Admin API responses).
     *
     * @param GetExtraPropertyValues $query
     * @param array<int, array<string, mixed>> $allDefinitions
     *
     * @return array<string, array<string, mixed>> Grouped by module; field values are [id_lang => value]
     */
    protected function loadLangScope(GetExtraPropertyValues $query, array $allDefinitions): array
    {
        $columnToPropertyMap = $this->buildColumnPropertyMap($allDefinitions, 'lang');
        if (empty($columnToPropertyMap)) {
            return [];
        }

        $langTableName = $this->prefix . ExtraPropertyNaming::extraTableName($query->getEntityName(), 'lang');
        $primaryKeyName = $query->getPrimaryKeyName();

        // id_lang is always included to group values by language
        $selectedColumns = array_merge(['id_lang'], array_keys($columnToPropertyMap));
        $quotedColumns = implode(', ', array_map(
            fn (string $col): string => $this->connection->quoteIdentifier($col),
            $selectedColumns
        ));

        try {
            $rows = $this->connection->fetchAllAssociative(
                sprintf(
                    'SELECT %s FROM %s WHERE %s = :entityId',
                    $quotedColumns,
                    $this->connection->quoteIdentifier($langTableName),
                    $this->connection->quoteIdentifier($primaryKeyName)
                ),
                ['entityId' => $query->getEntityId()]
            );
        } catch (Throwable) {
            return [];
        }

        if (empty($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $idLang = (int) $row['id_lang'];
            foreach ($columnToPropertyMap as $columnName => $propertyPath) {
                if (!array_key_exists($columnName, $row)) {
                    continue;
                }
                $result[$propertyPath['module_name']][$propertyPath['field_name']][$idLang] = $row[$columnName];
            }
        }

        return $result;
    }

    /**
     * Loads shop-scope extra properties (all shops) from *_extra_shop.
     *
     * Returns values indexed by id_shop (int).
     *
     * @param GetExtraPropertyValues $query
     * @param array<int, array<string, mixed>> $allDefinitions
     *
     * @return array<string, array<string, mixed>> Grouped by module; field values are [id_shop => value]
     */
    protected function loadShopScope(GetExtraPropertyValues $query, array $allDefinitions): array
    {
        $columnToPropertyMap = $this->buildColumnPropertyMap($allDefinitions, 'shop');
        if (empty($columnToPropertyMap)) {
            return [];
        }

        $shopTableName = $this->prefix . ExtraPropertyNaming::extraTableName($query->getEntityName(), 'shop');
        $primaryKeyName = $query->getPrimaryKeyName();

        // id_shop is always included to group values by shop
        $selectedColumns = array_merge(['id_shop'], array_keys($columnToPropertyMap));
        $quotedColumns = implode(', ', array_map(
            fn (string $col): string => $this->connection->quoteIdentifier($col),
            $selectedColumns
        ));

        try {
            $rows = $this->connection->fetchAllAssociative(
                sprintf(
                    'SELECT %s FROM %s WHERE %s = :entityId',
                    $quotedColumns,
                    $this->connection->quoteIdentifier($shopTableName),
                    $this->connection->quoteIdentifier($primaryKeyName)
                ),
                ['entityId' => $query->getEntityId()]
            );
        } catch (Throwable) {
            return [];
        }

        if (empty($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $idShop = (int) $row['id_shop'];
            foreach ($columnToPropertyMap as $columnName => $propertyPath) {
                if (!array_key_exists($columnName, $row)) {
                    continue;
                }
                $result[$propertyPath['module_name']][$propertyPath['field_name']][$idShop] = $row[$columnName];
            }
        }

        return $result;
    }

    /**
     * Builds a storage-column → property-path map for a given scope.
     *
     * @param array<int, array<string, mixed>> $allDefinitions
     * @param string $scope 'common', 'lang', or 'shop'
     *
     * @return array<string, array{module_name: string, field_name: string}>
     */
    protected function buildColumnPropertyMap(array $allDefinitions, string $scope): array
    {
        $map = [];
        foreach ($allDefinitions as $def) {
            if (($def['field_scope'] ?? '') !== $scope) {
                continue;
            }

            $fieldName = (string) ($def['field_name'] ?? '');
            $storageColumn = (string) ($def['storage_column_name'] ?? '');
            if ('' === $fieldName || '' === $storageColumn) {
                continue;
            }

            $map[$storageColumn] = [
                'module_name' => ExtraPropertyNaming::displayModuleKey($def['module_name'] ?? null),
                'field_name' => $fieldName,
            ];
        }

        return $map;
    }
}
