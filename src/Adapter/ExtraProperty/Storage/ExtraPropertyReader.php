<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\ExtraProperty\Storage;

use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyNaming;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyScope;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyScopeGrouper;
use PrestaShop\PrestaShop\Core\ExtraProperty\Repository\ExtraPropertyDefinitionRepositoryInterface;
use PrestaShop\PrestaShop\Core\ExtraProperty\Storage\ExtraPropertyReaderInterface;
use Throwable;

/**
 * Reads extra property values from the *_extra / *_extra_lang / *_extra_shop tables.
 *
 * Used by ObjectModel (via ServiceLocator) and front-office LazyArray contexts.
 * Values are grouped by module technical name then by field name.
 */
class ExtraPropertyReader implements ExtraPropertyReaderInterface
{
    public function __construct(
        protected readonly ExtraPropertyDefinitionRepositoryInterface $repository,
        protected readonly Connection $connection,
        protected readonly string $prefix
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraProperties(
        string $entityName,
        string $primaryKeyName,
        int $entityId,
        ?int $langId = null,
        ?int $shopId = null,
        bool $isLangMultishop = false,
        bool $displayFrontOnly = false,
        ?array $preloadedDefinitionRows = null
    ): array {
        $allDefinitions = null !== $preloadedDefinitionRows
            ? $preloadedDefinitionRows
            : $this->repository->getByEntityNameAllScopes($entityName);
        if (empty($allDefinitions)) {
            return [];
        }

        $definitionsByScope = ExtraPropertyScopeGrouper::groupDefinitionsByScope($allDefinitions);
        $propertiesByModule = [];

        foreach (ExtraPropertyScope::values() as $fieldScope) {
            $definitions = $definitionsByScope[$fieldScope] ?? [];
            if (empty($definitions)) {
                continue;
            }
            $this->hydrateExtraPropertiesScope(
                $entityName,
                $primaryKeyName,
                $entityId,
                $fieldScope,
                $definitions,
                $propertiesByModule,
                $langId,
                $shopId,
                $isLangMultishop,
                $displayFrontOnly
            );
        }

        return $propertiesByModule;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinitionsByModule(string $entityName, ?string $moduleName, ?string $fieldScope = null): array
    {
        $allDefinitions = $this->repository->getByEntityNameAllScopes($entityName);
        $normalizedModule = empty($moduleName) ? null : $moduleName;
        $normalizedScope = $fieldScope;

        $result = [];
        foreach ($allDefinitions as $definition) {
            $defModule = !empty($definition['module_name']) ? $definition['module_name'] : null;
            if ($normalizedModule !== null && $defModule !== $normalizedModule) {
                continue;
            }
            if (null !== $normalizedScope && ($definition['field_scope'] ?? null) !== $normalizedScope) {
                continue;
            }
            $result[] = $definition;
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $definitions
     * @param array<string, array<string, mixed>> $propertiesByModule
     */
    protected function hydrateExtraPropertiesScope(
        string $entityName,
        string $primaryKeyName,
        int $entityId,
        string $fieldScope,
        array $definitions,
        array &$propertiesByModule,
        ?int $langId,
        ?int $shopId,
        bool $isLangMultishop,
        bool $displayFrontOnly
    ): void {
        $extraTableName = ExtraPropertyNaming::extraTableName($entityName, $fieldScope);

        $columnToPropertyMap = [];
        foreach ($definitions as $definition) {
            if ($displayFrontOnly && empty($definition['display_front'])) {
                continue;
            }

            $fieldName = (string) ($definition['field_name'] ?? '');
            if ('' === $fieldName) {
                continue;
            }

            $moduleName = ExtraPropertyNaming::displayModuleKey($definition['module_name'] ?? null);
            $propertiesByModule[$moduleName] ??= [];
            $propertiesByModule[$moduleName][$fieldName] ??= null;

            $columnName = (string) ($definition['storage_column_name'] ?? ExtraPropertyNaming::storageColumnName((string) ($definition['module_name'] ?? ''), $fieldName));
            if ('' === $columnName) {
                continue;
            }

            $columnToPropertyMap[$columnName] = ['module_name' => $moduleName, 'field_name' => $fieldName];
        }

        if (empty($columnToPropertyMap) || $entityId <= 0) {
            return;
        }

        if ('lang' === $fieldScope) {
            if ((int) $langId <= 0) {
                return;
            }
            if ($isLangMultishop && (int) $shopId <= 0) {
                return;
            }
        } elseif ('shop' === $fieldScope && (int) $shopId <= 0) {
            return;
        }

        $qb = $this->connection->createQueryBuilder();
        $qb
            ->from($this->prefix . $extraTableName, 'extra')
            ->where('extra.' . $this->connection->quoteIdentifier($primaryKeyName) . ' = :entityId')
            ->setParameter('entityId', $entityId);

        $qb->select(...array_map(
            fn (string $col): string => 'extra.' . $this->connection->quoteIdentifier($col),
            array_keys($columnToPropertyMap)
        ));

        if ('lang' === $fieldScope) {
            $qb->andWhere('extra.id_lang = :langId')->setParameter('langId', (int) $langId);
            if ($isLangMultishop) {
                $qb->andWhere('extra.id_shop = :shopId')->setParameter('shopId', (int) $shopId);
            }
        } elseif ('shop' === $fieldScope) {
            $qb->andWhere('extra.id_shop = :shopId')->setParameter('shopId', (int) $shopId);
        }

        try {
            $row = $qb->executeQuery()->fetchAssociative();
        } catch (Throwable) {
            return;
        }

        if (!is_array($row)) {
            return;
        }

        foreach ($columnToPropertyMap as $columnName => $propertyPath) {
            if (!array_key_exists($columnName, $row)) {
                continue;
            }
            $propertiesByModule[$propertyPath['module_name']][$propertyPath['field_name']] = $row[$columnName];
        }
    }
}
