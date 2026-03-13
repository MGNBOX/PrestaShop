<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Storage;

/**
 * Reads extra property values for a given entity instance.
 *
 * Used by ObjectModel (via ServiceLocator) and front-office LazyArray contexts.
 * Returns values grouped by module name and then by field name.
 */
interface ExtraPropertyReaderInterface
{
    /**
     * Returns extra property values for one entity instance, grouped by module name.
     *
     * Format:
     * [
     *     'module_technical_name' => [
     *         'field_name' => 'value_or_array',
     *     ],
     * ]
     *
     * Lang-scope fields return an array keyed by id_lang.
     * Shop-scope fields return the value for the requested shopId (or null).
     *
     * @param string $entityName Entity table name (e.g. "product")
     * @param string $primaryKeyName PK column name (e.g. "id_product")
     * @param int $entityId
     * @param int|null $langId
     * @param int|null $shopId
     * @param bool $isLangMultishop Whether lang scope is shop-aware
     * @param bool $displayFrontOnly When true, only definitions with display_front = 1 are returned
     * @param array<int, array<string, mixed>>|null $preloadedDefinitionRows When set (e.g. from ObjectModel), skips a duplicate repository read; must match getByEntityNameAllScopes() shape
     *
     * @return array<string, array<string, mixed>>
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
    ): array;

    /**
     * Returns extra property definitions for a given entity, module, and optional scope.
     * Useful to enumerate all fields of a module on an entity.
     *
     * @param string $entityName
     * @param string|null $moduleName Null returns definitions for all modules (including core)
     * @param string|null $fieldScope When provided, filters by scope
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDefinitionsByModule(string $entityName, ?string $moduleName, ?string $fieldScope = null): array;
}
