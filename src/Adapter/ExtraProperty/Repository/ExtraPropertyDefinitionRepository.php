<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\ExtraProperty\Repository;

use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyDefinitionCollection;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyScope;
use PrestaShop\PrestaShop\Core\ExtraProperty\Repository\ExtraPropertyDefinitionRepositoryInterface;
use Validate;

/**
 * Reads extra property definitions from the extra_property_definition registry table.
 *
 * This implementation does not add any caching; wrap with CachedExtraPropertyRegistry for
 * production use. All entity/scope validation is centralized in normalizeEntityNameAndFieldScope().
 */
class ExtraPropertyDefinitionRepository implements ExtraPropertyDefinitionRepositoryInterface
{
    public const FIELD_SCOPE_COMMON = 'common';
    public const FIELD_SCOPE_LANG = 'lang';
    public const FIELD_SCOPE_SHOP = 'shop';

    public function __construct(
        protected readonly Connection $connection,
        protected readonly string $prefix,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinitionCollection(string $entityName): ExtraPropertyDefinitionCollection
    {
        return new ExtraPropertyDefinitionCollection($this->getByEntityNameAllScopes($entityName));
    }

    /**
     * {@inheritdoc}
     */
    public function getByEntityNameAllScopes(string $entityName): array
    {
        [$normalizedEntityName] = $this->normalizeEntityNameAndFieldScope($entityName, self::FIELD_SCOPE_COMMON);
        if (null === $normalizedEntityName) {
            return [];
        }

        $registryTable = $this->prefix . 'extra_property_definition';
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select([
                'eef.id_extra_property_definition',
                'eef.entity_name',
                'eef.field_scope',
                'eef.module_name',
                'eef.field_name',
                'eef.storage_column_name',
                'eef.field_type',
                'eef.symfony_field_type',
                'eef.property_path',
                'eef.sql_index',
                'eef.validator',
                'eef.display_front',
                'eef.display_api',
                'eef.display_bo',
                'eef.display_grid',
                'eef.grid_position',
                'eef.title_wording',
                'eef.title_domain',
                'eef.description_wording',
                'eef.description_domain',
            ])
            ->from($registryTable, 'eef')
            ->where('eef.entity_name = :entityName')
            ->setParameter('entityName', $normalizedEntityName)
            ->orderBy('eef.id_extra_property_definition', 'ASC');

        return $qb->executeQuery()->fetchAllAssociative() ?: [];
    }

    /**
     * {@inheritdoc}
     */
    public function getByEntityName(string $entityName, string $fieldScope = self::FIELD_SCOPE_COMMON): array
    {
        [$normalizedEntityName, $normalizedFieldScope] = $this->normalizeEntityNameAndFieldScope($entityName, $fieldScope);
        if (null === $normalizedEntityName || null === $normalizedFieldScope) {
            return [];
        }

        $result = array_filter(
            $this->getByEntityNameAllScopes($normalizedEntityName),
            static fn (array $definition): bool => ($definition['field_scope'] ?? null) === $normalizedFieldScope
        );

        return array_values($result);
    }

    /**
     * {@inheritdoc}
     */
    public function getByEntityAndFieldName(string $entityName, string $fieldName, string $fieldScope = self::FIELD_SCOPE_COMMON): ?array
    {
        if (!Validate::isTableOrIdentifier($fieldName)) {
            return null;
        }
        [$normalizedEntityName, $normalizedFieldScope] = $this->normalizeEntityNameAndFieldScope($entityName, $fieldScope);
        if (null === $normalizedEntityName || null === $normalizedFieldScope) {
            return null;
        }

        foreach ($this->getByEntityNameAllScopes($normalizedEntityName) as $definition) {
            if (
                ($definition['field_name'] ?? null) === $fieldName
                && ($definition['field_scope'] ?? null) === $normalizedFieldScope
            ) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasExtraProperties(string $entityName): bool
    {
        return !empty($this->getByEntityNameAllScopes($entityName));
    }

    /**
     * Finds one registry definition matching (entity_name, module_name, field_name, field_scope).
     * Uses the all-scopes retrieval internally.
     *
     * @param string $entityName normalized entity name
     * @param string|null $moduleName module technical name, or null for core fields
     * @param string $fieldName
     * @param string $fieldScope
     *
     * @return array<string, mixed>|null
     */
    public function findDefinitionByModuleAndField(string $entityName, ?string $moduleName, string $fieldName, string $fieldScope): ?array
    {
        // Normalize to '' for core fields (module_name IS '' in DB since it is NOT NULL DEFAULT '').
        $normalizedModule = (null === $moduleName || '' === $moduleName) ? '' : $moduleName;

        foreach ($this->getByEntityNameAllScopes($entityName) as $definition) {
            $defModule = (string) ($definition['module_name'] ?? '');

            if (
                $defModule === $normalizedModule
                && ($definition['field_name'] ?? null) === $fieldName
                && ($definition['field_scope'] ?? null) === $fieldScope
            ) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * Loads one definition row directly by primary key (bypasses internal all-scopes cache).
     *
     * @param int $id
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('*')
            ->from($this->prefix . 'extra_property_definition', 'eef')
            ->where('eef.id_extra_property_definition = :id')
            ->setParameter('id', $id);

        $row = $qb->executeQuery()->fetchAssociative();

        return is_array($row) ? $row : null;
    }

    /**
     * Saves (insert or update) one definition row.
     *
     * @param array<string, mixed> $data
     * @param int|null $existingId when provided, performs an UPDATE; otherwise INSERT
     *
     * @return int|false returns the id on success, false on failure
     */
    public function save(array $data, ?int $existingId = null): int|false
    {
        $table = $this->prefix . 'extra_property_definition';

        if (null !== $existingId) {
            $saved = (bool) $this->connection->update($table, $data, ['id_extra_property_definition' => $existingId]);

            return $saved ? $existingId : false;
        }

        $saved = (bool) $this->connection->insert($table, $data);
        if (!$saved) {
            return false;
        }

        return (int) $this->connection->lastInsertId();
    }

    /**
     * Deletes one definition row by primary key.
     * Also removes the associated lang rows.
     *
     * @param int $id
     *
     * @return bool
     */
    public function delete(int $id): bool
    {
        $table = $this->prefix . 'extra_property_definition';

        return (bool) $this->connection->delete($table, ['id_extra_property_definition' => $id]);
    }

    /**
     * Normalizes a legacy entity name suffix (product_lang → entity=product, scope=lang).
     *
     * @param string $entityName
     * @param string $fieldScope
     *
     * @return array{0: string|null, 1: string|null}
     */
    public function normalizeEntityNameAndFieldScope(string $entityName, string $fieldScope): array
    {
        $normalizedScope = strtolower(trim($fieldScope));
        $normalizedEntityName = $entityName;

        if (str_ends_with($normalizedEntityName, '_lang')) {
            $normalizedEntityName = substr($normalizedEntityName, 0, -5);
            if (self::FIELD_SCOPE_COMMON === $normalizedScope) {
                $normalizedScope = self::FIELD_SCOPE_LANG;
            } elseif (self::FIELD_SCOPE_LANG !== $normalizedScope) {
                return [null, null];
            }
        } elseif (str_ends_with($normalizedEntityName, '_shop')) {
            $normalizedEntityName = substr($normalizedEntityName, 0, -5);
            if (self::FIELD_SCOPE_COMMON === $normalizedScope) {
                $normalizedScope = self::FIELD_SCOPE_SHOP;
            } elseif (self::FIELD_SCOPE_SHOP !== $normalizedScope) {
                return [null, null];
            }
        }

        if (!in_array($normalizedScope, ExtraPropertyScope::values(), true)) {
            return [null, null];
        }
        if ('' === $normalizedEntityName || !Validate::isTableOrIdentifier($normalizedEntityName)) {
            return [null, null];
        }

        return [$normalizedEntityName, $normalizedScope];
    }
}
