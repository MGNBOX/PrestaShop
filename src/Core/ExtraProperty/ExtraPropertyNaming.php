<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty;

/**
 * Central naming helper for extra property tables, columns, and form/grid field names.
 *
 * All naming conventions are defined here once to avoid duplication across
 * ExtraPropertyReader, ExtraPropertyValueProvider, ExtraPropertyWriter,
 * ExtraPropertySchemaManager, ExtraPropertiesFormBuilderModifier,
 * ExtraPropertiesFormDataPersister, and ExtraPropertiesGridQueryBuilderModifier.
 *
 * Conventions:
 *  - extra tables  : {entity}_extra / {entity}_extra_lang / {entity}_extra_shop
 *  - storage column: {module_name}_{property_name} (or just {property_name} for core fields)
 *  - form field name: extra_{scope}_{module_name}_{property_name}
 *  - grid column id : extra_{scope}_{module_name}_{property_name}  (same as form field)
 *  - display module key: '_core' for fields without an owning module (module_name IS NULL)
 */
final class ExtraPropertyNaming
{
    /**
     * Module-name key used in grouped arrays for fields that have no owning module.
     */
    public const CORE_MODULE_KEY = '_core';

    /**
     * Returns the name of the extra value table (without DB prefix) for a given entity and scope.
     *
     * @param string $entityName Entity table name (e.g. 'product')
     * @param string $fieldScope 'common' | 'lang' | 'shop'
     *
     * @return string e.g. 'product_extra', 'product_extra_lang', 'product_extra_shop'
     */
    public static function extraTableName(string $entityName, string $fieldScope): string
    {
        return match ($fieldScope) {
            'lang' => $entityName . '_extra_lang',
            'shop' => $entityName . '_extra_shop',
            default => $entityName . '_extra',
        };
    }

    /**
     * Returns the physical SQL storage column name for a given module and field name.
     *
     * Core fields (empty module_name) use the bare field name.
     * Module fields use '{module_name}_{property_name}'.
     *
     * @param string $moduleName Module technical name, or '' for core properties
     * @param string $propertyName Property name as declared in the registry
     *
     * @return string e.g. 'ps_mymodule_video_link' or 'video_link'
     */
    public static function storageColumnName(string $moduleName, string $propertyName): string
    {
        if ('' === $moduleName || self::CORE_MODULE_KEY === $moduleName) {
            return $propertyName;
        }

        return $moduleName . '_' . $propertyName;
    }

    /**
     * Returns the form field name and grid column identifier for an extra property.
     *
     * The same format is used for:
     *   - unmapped Symfony form fields added by ExtraPropertiesFormBuilderModifier
     *   - submitted value lookups in ExtraPropertiesFormDataPersister
     *   - SELECT aliases in ExtraPropertiesGridQueryBuilderModifier
     *
     * @param string $moduleName Module technical name, or '_core' / '' for core properties
     * @param string $propertyName Property name as declared in the registry
     * @param string $fieldScope 'common' | 'lang' | 'shop'
     *
     * @return string e.g. 'extra_common_ps_mymodule_video_link'
     */
    public static function formFieldName(string $moduleName, string $propertyName, string $fieldScope): string
    {
        return 'extra_' . $fieldScope . '_' . self::normalizeModuleKey($moduleName) . '_' . $propertyName;
    }

    /**
     * Returns the display key for a module in grouped extra-property arrays.
     *
     * Maps '', null, and '_core' to the canonical '_core' key.
     * All other values are returned as-is.
     *
     * @param string|null $moduleName Raw module_name from the registry row ('' or module technical name)
     *
     * @return string '_core' or the module technical name
     */
    public static function displayModuleKey(?string $moduleName): string
    {
        if (null === $moduleName || '' === $moduleName || '_core' === $moduleName) {
            return self::CORE_MODULE_KEY;
        }

        return $moduleName;
    }

    /**
     * Normalizes a module name for use in identifiers (form field names, grid aliases).
     *
     * Returns '_core' for empty/null/core-sentinel values so identifiers are always valid.
     *
     * @param string|null $moduleName
     *
     * @return string
     */
    private static function normalizeModuleKey(?string $moduleName): string
    {
        return (null === $moduleName || '' === $moduleName) ? self::CORE_MODULE_KEY : $moduleName;
    }
}
