<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\ExtraProperty\Registry;

use PrestaShop\PrestaShop\Adapter\ExtraProperty\Repository\ExtraPropertyDefinitionRepository;
use PrestaShop\PrestaShop\Adapter\ExtraProperty\Schema\ExtraPropertySchemaManager;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyDefinitionCollection;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyNaming;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyScope;
use PrestaShop\PrestaShop\Core\ExtraProperty\Registry\EntityExtraFieldRegistryInterface;
use PrestaShop\PrestaShop\Core\ExtraProperty\Schema\ExtraPropertySchemaManagerInterface;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyType;
use PrestaShop\PrestaShop\Core\ExtraProperty\Schema\ColumnDefinitionMapper;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use Validate;

/**
 * Main registry implementation for extra property definitions and DDL on value tables.
 *
 * Implements EntityExtraFieldRegistryInterface (register/unregister plus read methods
 * delegated to ExtraPropertyDefinitionRepository).
 *
 * Orchestrates:
 *   - ExtraPropertyDefinitionRepository for definition persistence
 *   - ExtraPropertySchemaManagerInterface for DDL on *_extra / *_extra_lang / *_extra_shop tables
 */
class ExtraPropertyRegistry implements EntityExtraFieldRegistryInterface
{
    protected readonly LoggerInterface $logger;

    public function __construct(
        protected readonly ExtraPropertyDefinitionRepository $repository,
        protected readonly ExtraPropertySchemaManagerInterface $schemaManager,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinitionCollection(string $entityName): ExtraPropertyDefinitionCollection
    {
        return $this->repository->getDefinitionCollection($entityName);
    }

    /**
     * {@inheritdoc}
     */
    public function getByEntityNameAllScopes(string $entityName): array
    {
        return $this->repository->getByEntityNameAllScopes($entityName);
    }

    /**
     * {@inheritdoc}
     */
    public function getByEntityName(string $entityName, string $fieldScope = 'common'): array
    {
        return $this->repository->getByEntityName($entityName, $fieldScope);
    }

    /**
     * {@inheritdoc}
     */
    public function getByEntityAndFieldName(string $entityName, string $fieldName, string $fieldScope = 'common'): ?array
    {
        return $this->repository->getByEntityAndFieldName($entityName, $fieldName, $fieldScope);
    }

    /**
     * {@inheritdoc}
     */
    public function hasExtraProperties(string $entityName): bool
    {
        return $this->repository->hasExtraProperties($entityName);
    }

    /**
     * {@inheritdoc}
     */
    public function register(string $entityName, string $fieldName, ?string $defaultModuleName = null, array $options = []): bool
    {
        if (!Validate::isTableOrIdentifier($fieldName)) {
            return false;
        }

        $fieldScope = $this->getFieldScopeOption($options);
        [$normalizedEntityName, $normalizedFieldScope] = $this->repository->normalizeEntityNameAndFieldScope($entityName, $fieldScope);
        if (null === $normalizedEntityName || null === $normalizedFieldScope) {
            return false;
        }

        $moduleName = $defaultModuleName;
        if (array_key_exists('module_name', $options) && is_string($options['module_name']) && '' !== trim($options['module_name'])) {
            $moduleName = $options['module_name'];
        }
        if (null !== $moduleName && !Validate::isModuleName($moduleName)) {
            return false;
        }

        $fieldType = $this->resolveFieldType($options);
        $sqlColumnDefinition = ColumnDefinitionMapper::getSqlDefinition($fieldType, $options);
        $storageColumnName = ExtraPropertyNaming::storageColumnName($moduleName ?? '', $fieldName);

        if (!$this->isValidSqlIdentifier($storageColumnName)) {
            return false;
        }

        $sqlIndex = $this->getSqlIndexOption($options);

        // 1. Ensure the *_extra table and column exist.
        try {
            $this->schemaManager->ensureExtraTableAndColumn(
                $normalizedEntityName,
                $normalizedFieldScope,
                $storageColumnName,
                $sqlColumnDefinition,
                $sqlIndex
            );
        } catch (Throwable $exception) {
            $this->logger->error(
                'Failed to create extra table/column: {message}',
                ['message' => $exception->getMessage(), 'exception' => $exception]
            );

            return false;
        }

        // 2. Insert or update the registry row.
        $existingDefinition = $this->repository->findDefinitionByModuleAndField(
            $normalizedEntityName,
            $moduleName,
            $fieldName,
            $normalizedFieldScope
        );

        $data = [
            // module_name is NOT NULL DEFAULT '' in DB; use '' for core fields (no owning module).
            'module_name' => $moduleName ?? '',
            'field_scope' => $normalizedFieldScope,
            'storage_column_name' => $storageColumnName,
            'field_type' => $fieldType->value,
            'symfony_field_type' => $this->getNullableStringOption($options, 'symfony_field_type'),
            'property_path' => $this->getNullableStringOption($options, 'property_path'),
            'sql_index' => $sqlIndex,
            'validator' => $this->getNullableStringOption($options, 'validator'),
            'display_front' => (int) $this->getBoolOption($options, 'display_front', false),
            'display_api' => (int) $this->getBoolOption($options, 'display_api', false),
            'display_bo' => (int) $this->getBoolOption($options, 'display_bo', true),
            'display_grid' => (int) $this->getBoolOption($options, 'display_grid', false),
            'grid_position' => $this->getNullableStringOption($options, 'grid_position'),
            'title_wording' => $this->getNullableStringOption($options, 'title_wording'),
            'title_domain' => $this->getNullableStringOption($options, 'title_domain'),
            'description_wording' => $this->getNullableStringOption($options, 'description_wording'),
            'description_domain' => $this->getNullableStringOption($options, 'description_domain'),
        ];

        $existingId = null !== $existingDefinition ? (int) $existingDefinition['id_extra_property_definition'] : null;
        if (null === $existingId) {
            $data['entity_name'] = $normalizedEntityName;
            $data['field_name'] = $fieldName;
        }

        $savedId = $this->repository->save($data, $existingId);
        if (false === $savedId) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function unregister(string $entityName, string $fieldName, ?string $moduleName, ExtraPropertyScope|string $fieldScope = 'common', bool $dropColumn = false): bool
    {
        if (!Validate::isTableOrIdentifier($fieldName)) {
            return false;
        }
        $fieldScope = $fieldScope instanceof ExtraPropertyScope ? $fieldScope->value : $fieldScope;
        [$normalizedEntityName, $normalizedFieldScope] = $this->repository->normalizeEntityNameAndFieldScope($entityName, $fieldScope);
        if (null === $normalizedEntityName || null === $normalizedFieldScope) {
            return false;
        }

        $existingDefinition = $this->repository->findDefinitionByModuleAndField(
            $normalizedEntityName,
            $moduleName,
            $fieldName,
            $normalizedFieldScope
        );
        if (null === $existingDefinition) {
            return true;
        }

        return $this->unregisterById((int) $existingDefinition['id_extra_property_definition'], $dropColumn);
    }

    /**
     * {@inheritdoc}
     */
    public function unregisterById(int $idExtraPropertyDefinition, bool $dropColumn = false): bool
    {
        if ($idExtraPropertyDefinition <= 0) {
            return false;
        }

        // Load row to get entity/scope/column before deletion.
        $definition = $this->getDefinitionById($idExtraPropertyDefinition);
        if (null === $definition) {
            return true;
        }

        if ($dropColumn) {
            [$normalizedEntityName, $normalizedFieldScope] = $this->repository->normalizeEntityNameAndFieldScope(
                $definition['entity_name'],
                $definition['field_scope']
            );
            if (null === $normalizedEntityName || null === $normalizedFieldScope) {
                return false;
            }

            $storageColumnName = $this->resolveStorageColumnName($definition);

            try {
                $this->schemaManager->dropExtraColumnIfExists($normalizedEntityName, $normalizedFieldScope, $storageColumnName);
            } catch (Throwable $exception) {
                $this->logger->error(
                    'Failed to drop extra column: {message}',
                    ['message' => $exception->getMessage(), 'exception' => $exception]
                );

                return false;
            }
        }

        return $this->repository->delete($idExtraPropertyDefinition);
    }

    /**
     * Loads one registry row by primary key directly from DB (bypasses cache to get fresh data).
     *
     * @return array<string, mixed>|null
     */
    public function getDefinitionById(int $idExtraPropertyDefinition): ?array
    {
        return $this->repository->findById($idExtraPropertyDefinition);
    }

    protected function resolveStorageColumnName(array $definition): string
    {
        $storageColumnName = $definition['storage_column_name'] ?? null;
        if (is_string($storageColumnName) && '' !== $storageColumnName && $this->isValidSqlIdentifier($storageColumnName)) {
            return $storageColumnName;
        }

        return ExtraPropertyNaming::storageColumnName(
            isset($definition['module_name']) && is_string($definition['module_name']) ? $definition['module_name'] : '',
            (string) ($definition['field_name'] ?? '')
        );
    }

    protected function getFieldScopeOption(array $options): string
    {
        $scopeOptionKey = array_key_exists('field_scope', $options) ? 'field_scope' : 'scope';
        $scopeOptionValue = array_key_exists($scopeOptionKey, $options)
            ? $options[$scopeOptionKey]
            : ExtraPropertyScope::Common;

        if ($scopeOptionValue instanceof ExtraPropertyScope) {
            $normalizedScope = $scopeOptionValue->value;
        } elseif (is_scalar($scopeOptionValue)) {
            $normalizedScope = strtolower(trim((string) $scopeOptionValue));
        } else {
            $normalizedScope = ExtraPropertyScope::Common->value;
        }

        return in_array($normalizedScope, ExtraPropertyScope::values(), true)
            ? $normalizedScope
            : ExtraPropertyScope::Common->value;
    }

    protected function getSqlIndexOption(array $options): string
    {
        $sqlIndex = $this->getStringOption($options, 'sql_index', ExtraPropertySchemaManager::SQL_INDEX_NONE);
        $normalizedSqlIndex = $this->normalizeSqlIndex($sqlIndex);

        return $normalizedSqlIndex ?? ExtraPropertySchemaManager::SQL_INDEX_NONE;
    }

    protected function normalizeSqlIndex(string $sqlIndex): ?string
    {
        $normalizedSqlIndex = strtolower(trim($sqlIndex));
        if ('index' === $normalizedSqlIndex) {
            $normalizedSqlIndex = ExtraPropertySchemaManager::SQL_INDEX_KEY;
        }
        $allowed = [
            ExtraPropertySchemaManager::SQL_INDEX_NONE,
            ExtraPropertySchemaManager::SQL_INDEX_KEY,
            ExtraPropertySchemaManager::SQL_INDEX_UNIQUE,
        ];
        if (!in_array($normalizedSqlIndex, $allowed, true)) {
            return null;
        }

        return $normalizedSqlIndex;
    }

    protected function resolveFieldType(array $options): ExtraPropertyType
    {
        return ExtraPropertyType::fromRegisterOption($options['type'] ?? null);
    }

    protected function isValidSqlIdentifier(string $identifier): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_]{1,64}$/', $identifier);
    }

    protected function getStringOption(array $options, string $optionKey, string $defaultValue): string
    {
        $optionValue = array_key_exists($optionKey, $options) ? $options[$optionKey] : $defaultValue;
        if (!is_scalar($optionValue)) {
            return $defaultValue;
        }

        return (string) $optionValue;
    }

    protected function getNullableStringOption(array $options, string $optionKey): ?string
    {
        if (!array_key_exists($optionKey, $options) || null === $options[$optionKey]) {
            return null;
        }
        if (!is_scalar($options[$optionKey])) {
            return null;
        }

        return (string) $options[$optionKey];
    }

    protected function getBoolOption(array $options, string $optionKey, bool $defaultValue): bool
    {
        if (!array_key_exists($optionKey, $options)) {
            return $defaultValue;
        }

        return (bool) $options[$optionKey];
    }
}
