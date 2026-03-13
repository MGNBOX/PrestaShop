<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\ExtraProperty\BackOffice;

use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyNaming;
use Throwable;

/**
 * Loads extra properties values for Back Office forms using Doctrine DBAL.
 *
 * Returned structure:
 * - entity scope: ['module' => ['field' => scalar]]
 * - lang scope:   ['module' => ['field' => [id_lang => value]]]
 * - shop scope:   ['module' => ['field' => scalar]] (shop context)
 */
class ExtraPropertiesFormDataLoader
{
    /**
     * @param string $prefix Database prefix (e.g. 'ps_')
     */
    public function __construct(
        protected readonly Connection $connection,
        protected readonly string $prefix,
    ) {
    }

    /**
     * @param string $entityName
     * @param int $entityId
     * @param int $shopId Current shop context ID (used for lang/shop scopes)
     * @param array<int, array<string, mixed>> $definitions Already filtered definitions (display_bo=1)
     *
     * @return array<string, array<string, mixed>>
     */
    public function load(string $entityName, int $entityId, int $shopId, array $definitions): array
    {
        if ($entityId <= 0 || empty($definitions)) {
            return [];
        }

        $storageEntityName = $this->resolveStorageEntityName($entityName, $definitions);

        $result = [];
        $entityResult = $this->loadEntityScope($storageEntityName, $entityId, $definitions);
        $langResult = $this->loadLangScope($storageEntityName, $entityId, $shopId, $definitions);
        $shopResult = $this->loadShopScope($storageEntityName, $entityId, $shopId, $definitions);

        foreach ([$entityResult, $langResult, $shopResult] as $partial) {
            foreach ($partial as $moduleName => $fields) {
                foreach ($fields as $fieldName => $value) {
                    $result[$moduleName][$fieldName] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $definitions
     */
    protected function resolveStorageEntityName(string $fallbackEntityName, array $definitions): string
    {
        $definitionEntityName = $definitions[0]['entity_name'] ?? null;
        if (is_string($definitionEntityName) && '' !== trim($definitionEntityName)) {
            return trim($definitionEntityName);
        }

        return $fallbackEntityName;
    }

    /**
     * @param array<int, array<string, mixed>> $definitions
     *
     * @return array<string, array<string, mixed>>
     */
    protected function loadEntityScope(string $entityName, int $entityId, array $definitions): array
    {
        $columnToPropertyMap = $this->buildColumnPropertyMap($definitions, 'common');
        if (empty($columnToPropertyMap)) {
            return [];
        }

        $tableName = $this->prefix . ExtraPropertyNaming::extraTableName($entityName, 'common');
        $primaryKeyName = 'id_' . $entityName;

        $selectedColumns = implode(', ', array_map(
            fn (string $col): string => $this->connection->quoteIdentifier($col),
            array_keys($columnToPropertyMap)
        ));

        try {
            $row = $this->connection->fetchAssociative(
                sprintf(
                    'SELECT %s FROM %s WHERE %s = :entityId',
                    $selectedColumns,
                    $this->connection->quoteIdentifier($tableName),
                    $this->connection->quoteIdentifier($primaryKeyName)
                ),
                ['entityId' => $entityId]
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
     * @param array<int, array<string, mixed>> $definitions
     *
     * @return array<string, array<string, mixed>>
     */
    protected function loadLangScope(string $entityName, int $entityId, int $shopId, array $definitions): array
    {
        $columnToPropertyMap = $this->buildColumnPropertyMap($definitions, 'lang');
        if (empty($columnToPropertyMap) || $shopId <= 0) {
            return [];
        }

        $tableName = $this->prefix . ExtraPropertyNaming::extraTableName($entityName, 'lang');
        $primaryKeyName = 'id_' . $entityName;

        // Always include id_lang to group values
        $selectedColumns = array_merge(['id_lang'], array_keys($columnToPropertyMap));
        $quotedColumns = implode(', ', array_map(
            fn (string $col): string => $this->connection->quoteIdentifier($col),
            $selectedColumns
        ));

        try {
            $rows = $this->connection->fetchAllAssociative(
                sprintf(
                    'SELECT %s FROM %s WHERE %s = :entityId AND id_shop = :shopId',
                    $quotedColumns,
                    $this->connection->quoteIdentifier($tableName),
                    $this->connection->quoteIdentifier($primaryKeyName)
                ),
                ['entityId' => $entityId, 'shopId' => $shopId]
            );
        } catch (Throwable) {
            return [];
        }

        if (empty($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $idLang = (int) ($row['id_lang'] ?? 0);
            if ($idLang <= 0) {
                continue;
            }

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
     * @param array<int, array<string, mixed>> $definitions
     *
     * @return array<string, array<string, mixed>>
     */
    protected function loadShopScope(string $entityName, int $entityId, int $shopId, array $definitions): array
    {
        $columnToPropertyMap = $this->buildColumnPropertyMap($definitions, 'shop');
        if (empty($columnToPropertyMap) || $shopId <= 0) {
            return [];
        }

        $tableName = $this->prefix . ExtraPropertyNaming::extraTableName($entityName, 'shop');
        $primaryKeyName = 'id_' . $entityName;

        $selectedColumns = implode(', ', array_map(
            fn (string $col): string => $this->connection->quoteIdentifier($col),
            array_keys($columnToPropertyMap)
        ));

        try {
            $row = $this->connection->fetchAssociative(
                sprintf(
                    'SELECT %s FROM %s WHERE %s = :entityId AND id_shop = :shopId',
                    $selectedColumns,
                    $this->connection->quoteIdentifier($tableName),
                    $this->connection->quoteIdentifier($primaryKeyName)
                ),
                ['entityId' => $entityId, 'shopId' => $shopId]
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
     * @param array<int, array<string, mixed>> $definitions
     *
     * @return array<string, array{module_name: string, field_name: string}>
     */
    protected function buildColumnPropertyMap(array $definitions, string $scope): array
    {
        $result = [];
        foreach ($definitions as $definition) {
            if (($definition['field_scope'] ?? null) !== $scope) {
                continue;
            }
            $columnName = (string) ($definition['storage_column_name'] ?? '');
            $fieldName = (string) ($definition['field_name'] ?? '');
            $moduleName = ExtraPropertyNaming::displayModuleKey($definition['module_name'] ?? null);

            if ('' === $columnName || '' === $fieldName) {
                continue;
            }
            $result[$columnName] = [
                'module_name' => $moduleName,
                'field_name' => $fieldName,
            ];
        }

        return $result;
    }
}
