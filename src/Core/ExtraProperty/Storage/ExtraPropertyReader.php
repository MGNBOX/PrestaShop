<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Storage;

use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\QueryResult\ExtraPropertyDefinitionInfo;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyDefinitionCollection;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyNaming;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyScope;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyScopeGrouper;
use PrestaShop\PrestaShop\Core\ExtraProperty\Repository\ExtraPropertyDefinitionRepositoryInterface;
use Throwable;

/**
 * Reads extra property values from the *_extra / *_extra_lang / *_extra_shop tables.
 *
 * Used by ObjectModel (via ServiceLocator) and front-office LazyArray / presenter contexts.
 * Values are grouped by module technical name then by field name.
 *
 * Also provides findCustomFieldDefinition() (formerly on ExtraPropertyValueProvider) to look
 * up a single definition by field name across all scopes.
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
        ?ExtraPropertyDefinitionCollection $preloadedDefinitions = null
    ): array {
        $allDefinitions = null !== $preloadedDefinitions
            ? $preloadedDefinitions->toArray()
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
                $isLangMultishop
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

        return array_values(array_filter(
            $allDefinitions,
            static function (ExtraPropertyDefinitionInfo $definition) use ($normalizedModule, $fieldScope): bool {
                $defModule = $definition->getModuleName();
                if (null !== $normalizedModule && $defModule !== $normalizedModule) {
                    return false;
                }
                if (null !== $fieldScope && $definition->getFieldScope() !== $fieldScope) {
                    return false;
                }

                return true;
            }
        ));
    }

    /**
     * {@inheritdoc}
     *
     * When $fieldScope is null, returns the single matching definition or null when ambiguous.
     */
    public function findCustomFieldDefinition(string $entityName, string $fieldName, ?string $fieldScope = null): ?ExtraPropertyDefinitionInfo
    {
        if (null !== $fieldScope && !in_array($fieldScope, ExtraPropertyScope::values(), true)) {
            return null;
        }

        $matchingDefinitions = [];
        foreach ($this->repository->getByEntityNameAllScopes($entityName) as $definition) {
            if ($definition->getPropertyName() !== $fieldName) {
                continue;
            }

            $definitionScope = $definition->getFieldScope();
            if (!in_array($definitionScope, ExtraPropertyScope::values(), true)) {
                continue;
            }
            if (null !== $fieldScope && $definitionScope !== $fieldScope) {
                continue;
            }

            $matchingDefinitions[] = $definition;
        }

        if (null !== $fieldScope) {
            return $matchingDefinitions[0] ?? null;
        }
        if (count($matchingDefinitions) !== 1) {
            return null;
        }

        return $matchingDefinitions[0];
    }

    /**
     * Hydrates extra properties for one scope into $propertiesByModule.
     *
     * When $langId is null (lang scope) or $shopId is null (shop scope), all rows are fetched
     * and grouped by id_lang / id_shop respectively (used by BO forms and Admin API).
     * When specific IDs are given, a single row is fetched (FO pattern).
     *
     * @param list<ExtraPropertyDefinitionInfo> $definitions
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
        bool $isLangMultishop
    ): void {
        $groupByLang = 'lang' === $fieldScope && null === $langId;
        $groupByShop = 'shop' === $fieldScope && null === $shopId;
        $isGrouped = $groupByLang || $groupByShop;

        $extraTableName = ExtraPropertyNaming::extraTableName($entityName, $fieldScope);

        $columnToPropertyMap = [];
        foreach ($definitions as $definition) {
            $propertyName = $definition->getPropertyName();
            if ('' === $propertyName) {
                continue;
            }

            $moduleName = ExtraPropertyNaming::displayModuleKey($definition->getModuleName());
            $propertiesByModule[$moduleName] ??= [];
            $propertiesByModule[$moduleName][$propertyName] ??= ($isGrouped ? [] : null);

            $columnName = ExtraPropertyNaming::storageColumnName($definition->getModuleName() ?? '', $propertyName);
            if ('' === $columnName) {
                continue;
            }

            $columnToPropertyMap[$columnName] = ['module_name' => $moduleName, 'property_name' => $propertyName];
        }

        if (empty($columnToPropertyMap) || $entityId <= 0) {
            return;
        }

        if ('lang' === $fieldScope && null !== $langId && (int) $langId <= 0) {
            return;
        }
        if ('shop' === $fieldScope && null !== $shopId && (int) $shopId <= 0) {
            return;
        }
        if ('lang' === $fieldScope && $isLangMultishop && null !== $shopId && (int) $shopId <= 0) {
            return;
        }

        $qb = $this->connection->createQueryBuilder();
        $qb
            ->from($this->prefix . $extraTableName, 'extra')
            ->where('extra.' . $this->connection->quoteIdentifier($primaryKeyName) . ' = :entityId')
            ->setParameter('entityId', $entityId);

        $selectCols = array_map(
            fn (string $col): string => 'extra.' . $this->connection->quoteIdentifier($col),
            array_keys($columnToPropertyMap)
        );

        if ('lang' === $fieldScope) {
            if ($groupByLang) {
                array_unshift($selectCols, 'extra.' . $this->connection->quoteIdentifier('id_lang'));
            } else {
                $qb->andWhere('extra.id_lang = :langId')->setParameter('langId', (int) $langId);
            }
            if ($isLangMultishop && null !== $shopId) {
                $qb->andWhere('extra.id_shop = :shopId')->setParameter('shopId', (int) $shopId);
            }
        } elseif ('shop' === $fieldScope) {
            if ($groupByShop) {
                array_unshift($selectCols, 'extra.' . $this->connection->quoteIdentifier('id_shop'));
            } else {
                $qb->andWhere('extra.id_shop = :shopId')->setParameter('shopId', (int) $shopId);
            }
        }

        $qb->select(...$selectCols);

        try {
            if ($isGrouped) {
                $rows = $qb->executeQuery()->fetchAllAssociative();
            } else {
                $singleRow = $qb->executeQuery()->fetchAssociative();
                $rows = is_array($singleRow) ? [$singleRow] : [];
            }
        } catch (Throwable) {
            return;
        }

        foreach ($rows as $row) {
            if ($groupByLang) {
                $groupKey = (int) ($row['id_lang'] ?? 0);
                foreach ($columnToPropertyMap as $columnName => $propertyPath) {
                    if (array_key_exists($columnName, $row)) {
                        $propertiesByModule[$propertyPath['module_name']][$propertyPath['property_name']][$groupKey] = $row[$columnName];
                    }
                }
            } elseif ($groupByShop) {
                $groupKey = (int) ($row['id_shop'] ?? 0);
                foreach ($columnToPropertyMap as $columnName => $propertyPath) {
                    if (array_key_exists($columnName, $row)) {
                        $propertiesByModule[$propertyPath['module_name']][$propertyPath['property_name']][$groupKey] = $row[$columnName];
                    }
                }
            } else {
                foreach ($columnToPropertyMap as $columnName => $propertyPath) {
                    if (array_key_exists($columnName, $row)) {
                        $propertiesByModule[$propertyPath['module_name']][$propertyPath['property_name']] = $row[$columnName];
                    }
                }
            }
        }
    }
}
