<?php

/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Definition;

use PrestaShop\PrestaShop\Core\ExtraProperty\Schema\ExtraPropertySchemaManagerInterface;
use PrestaShop\PrestaShop\Core\ExtraProperty\Validation\ExtraPropertyValidationInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Write-only registry implementation: register/unregister extra property definitions.
 *
 * Orchestrates:
 *   - ExtraPropertyDefinitionRepositoryInterface (read) for pre-flight existence checks
 *   - ExtraPropertyDefinitionWriterInterface for definition persistence (save/delete)
 *   - ExtraPropertySchemaManagerInterface for DDL on *_extra / *_extra_lang / *_extra_shop tables
 *
 * Does NOT handle cache invalidation: wrap with CachedExtraPropertyRegistry for that concern.
 */
class ExtraPropertyRegistry implements ExtraPropertyRegistryInterface
{
    public function __construct(
        protected readonly ExtraPropertyDefinitionRepositoryInterface $readRepository,
        protected readonly ExtraPropertyDefinitionWriterInterface $writeRepository,
        protected readonly ExtraPropertySchemaManagerInterface $schemaManager,
        protected readonly ExtraPropertyValidationInterface $validator,
        protected readonly LoggerInterface $logger,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * Constructor-level validations (entityName, propertyName, associatedForms/Grids format,
     * labelWording, moduleName format, storageColumnName length) are enforced by ExtraPropertyDefinition itself.
     *
     * The registry additionally validates:
     * - scope-uniqueness: a module cannot register the same propertyName in two different scopes for the same entity
     * - immutability of storage-critical fields on already-registered definitions
     *
     * Operation order: validate immutability → persist to DB → create DDL.
     * Note: if DDL creation fails after DB persistence, a retry of register() will be a no-op (column already exists).
     * Changing a field's type requires unregister() + register() — automatic column migration is not supported.
     */
    public function register(ExtraPropertyDefinition $definition): bool
    {
        $entityName = $definition->getEntityName();
        $propertyName = $definition->getPropertyName();

        // Resolve module name: '_core' is a display-only sentinel — never stored in DB.
        $rawModuleName = $definition->getModuleName();
        $moduleName = (null !== $rawModuleName && '' !== $rawModuleName && ExtraPropertyDefinition::CORE_MODULE_KEY !== $rawModuleName)
            ? $rawModuleName
            : null;

        $scope = $definition->getScope();
        $normalizedScope = $scope->value;

        // 1. Validate scope-uniqueness: same entity + module + propertyName must not exist with a different scope.
        foreach (ExtraPropertyScope::cases() as $otherScope) {
            if ($otherScope === $scope) {
                continue;
            }
            $conflict = $this->readRepository->findDefinitionByModuleAndField(
                $entityName,
                $moduleName,
                $propertyName,
                $otherScope->value
            );
            if (null !== $conflict) {
                $this->logger->error(
                    'Cannot register extra property {entity}.{field}: already registered with scope "{existing_scope}", cannot also register with scope "{new_scope}".',
                    ['entity' => $entityName, 'field' => $propertyName, 'existing_scope' => $otherScope->value, 'new_scope' => $normalizedScope]
                );

                return false;
            }
        }

        // 2. Check for immutable storage-critical changes on an existing definition.
        $existingDefinition = $this->readRepository->findDefinitionByModuleAndField(
            $entityName,
            $moduleName,
            $propertyName,
            $normalizedScope
        );

        if (null !== $existingDefinition && $this->hasStorageChanges($definition, $existingDefinition)) {
            $this->logger->error(
                'Refusing to modify storage-critical fields (type/size/scope/defaultValue) for existing extra property {entity}.{field}.',
                ['entity' => $entityName, 'field' => $propertyName]
            );

            return false;
        }

        // 3. Insert or update the registry row.
        $savedId = $this->writeRepository->save(
            $definition,
            $entityName,
            $propertyName,
            $moduleName,
            $normalizedScope
        );

        if (false === $savedId) {
            return false;
        }

        // 4. Ensure the *_extra table and column exist (DDL after DB write).
        try {
            $this->schemaManager->ensureExtraTableAndColumn($definition);
        } catch (Throwable $exception) {
            $this->logger->error(
                'Failed to create extra table/column: {message}',
                ['message' => $exception->getMessage(), 'exception' => $exception]
            );

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function unregister(ExtraPropertyDefinition $definition, bool $dropColumn = false): bool
    {
        if ($dropColumn) {
            try {
                $this->schemaManager->dropExtraColumnIfExists($definition);
            } catch (Throwable $exception) {
                $this->logger->error(
                    'Failed to drop extra column: {message}',
                    ['message' => $exception->getMessage(), 'exception' => $exception]
                );

                return false;
            }
        }

        return $this->writeRepository->deleteByDefinition($definition);
    }

    /**
     * Returns true when $incoming would change a storage-critical field on $existing.
     *
     * These fields affect the SQL column schema (ALTER TABLE) and are immutable once registered.
     * Display flags, labels, form options, positions, and index type can be updated freely.
     *
     * Note: nullable and enumValues are not persisted in the registry and therefore cannot be
     * compared here; they are applied only at initial column creation.
     */
    protected function hasStorageChanges(ExtraPropertyDefinition $incoming, ExtraPropertyDefinition $existing): bool
    {
        return $incoming->getType() !== $existing->getType()
            || $incoming->getScope() !== $existing->getScope()
            || $incoming->getSize() !== $existing->getSize()
            || $incoming->getDefaultValue() !== $existing->getDefaultValue();
    }
}
