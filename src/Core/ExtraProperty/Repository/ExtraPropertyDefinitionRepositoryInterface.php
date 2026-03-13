<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Repository;

use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyDefinitionCollection;

/**
 * Read-only repository for extra property definitions.
 *
 * Provides definition look-ups used by the registry, the BO form/grid modifiers,
 * and the ObjectModel. Implementations may be decorated with a cache layer.
 */
interface ExtraPropertyDefinitionRepositoryInterface
{
    /**
     * Returns all extra property definitions for an entity as a typed collection.
     * Equivalent to wrapping getByEntityNameAllScopes() in an ExtraPropertyDefinitionCollection.
     *
     * @param string $entityName
     *
     * @return ExtraPropertyDefinitionCollection
     */
    public function getDefinitionCollection(string $entityName): ExtraPropertyDefinitionCollection;

    /**
     * Returns all extra property definitions for an entity, across all scopes.
     * Labels are stored as translation wording/domain pairs in the definition row.
     *
     * @param string $entityName
     *
     * @return array<int, array<string, mixed>>
     */
    public function getByEntityNameAllScopes(string $entityName): array;

    /**
     * Returns extra property definitions for one entity + one scope.
     *
     * @param string $entityName
     * @param string $fieldScope 'common' | 'lang' | 'shop'
     *
     * @return array<int, array<string, mixed>>
     */
    public function getByEntityName(string $entityName, string $fieldScope = 'common'): array;

    /**
     * Returns a single definition matching entity + field name + scope.
     * Returns null when not found or when parameters fail validation.
     *
     * @param string $entityName
     * @param string $fieldName
     * @param string $fieldScope 'common' | 'lang' | 'shop'
     *
     * @return array<string, mixed>|null
     */
    public function getByEntityAndFieldName(string $entityName, string $fieldName, string $fieldScope = 'common'): ?array;

    /**
     * Returns true when at least one extra property is defined for the given entity (any scope).
     *
     * @param string $entityName
     *
     * @return bool
     */
    public function hasExtraProperties(string $entityName): bool;
}
