<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty;

/**
 * Value object holding all options for registering an extra property definition.
 *
 * Replaces the raw options array used in Module::registerExtraProperty() and
 * EntityExtraFieldRegistryInterface::register() calls. Provides type-safety and IDE auto-complete
 * while keeping a toArray()/fromArray() bridge for compatibility with the array-based registry interface.
 *
 * All properties are public readonly to allow direct property access without getters.
 *
 * About BO label translations:
 * - title/description are not stored as per-language values in SQL;
 * - use wording + domain pairs (titleWording/titleDomain and descriptionWording/descriptionDomain);
 * - BO rendering translates them at runtime with Translator::trans();
 * - for BO translation pages to discover those strings, modules must expose the same wordings through
 *   explicit $this->trans('...', [], 'Modules.<Module>.Admin') calls (and/or module XLF files).
 *
 * @see EntityExtraFieldRegistryInterface::register()
 * @see Schema\ColumnDefinitionMapper
 */
final class ExtraPropertyOptions
{
    /**
     * @param ExtraPropertyType $type
     *                                Field storage type. Determines the SQL column type via ColumnDefinitionMapper.
     * @param ExtraPropertyScope $scope
     *                                  Storage scope: Common (entity-level), Lang (per-language), Shop (per-shop)
     * @param list<string>|null $enumValues
     *                                      For Choice type: the SQL ENUM allowed values. Generates ENUM('v1','v2') DDL.
     *                                      Ignored for other types.
     * @param scalar|null $defaultValue
     *                                  If provided, adds a DEFAULT clause in the DDL, quoted according to field type
     * @param bool $nullable
     *                       Controls NULL vs NOT NULL in the DDL
     * @param string|null $moduleName
     *                                Override the owning module name. Null means the calling module.
     * @param string|null $titleWording
     *                                  Translation wording key shown in BO forms. Example: "Theme color".
     * @param string|null $titleDomain
     *                                 Translation domain used for the title wording. Example: "Modules.MyModule.Admin".
     * @param string|null $descriptionWording
     *                                        Translation wording key shown as BO help text
     * @param string|null $descriptionDomain
     *                                       Translation domain used for the description wording
     * @param string $sqlIndex
     *                         SQL index strategy on the storage column: "none", "key", or "unique"
     * @param string|null $symfonyFieldType
     *                                      Fully-qualified Symfony Form type class name used by the BO form renderer
     * @param string|null $validator
     *                               PrestaShop Validate method name (e.g. "isUrl", "isBool") applied before persistence.
     * @param bool $displayFront
     *                           Expose this field in front-office (FO) LazyArray contexts
     * @param bool $displayApi
     *                         Include this field in Admin API JSON responses
     * @param bool $displayBo
     *                        Show and edit this field in BO forms
     * @param bool $displayGrid
     *                          Display this field as a column in BO Symfony grids
     * @param string|null $propertyPath
     *                                  Dot-notation Symfony form path specifying where the field is injected in the form tree
     * @param string|int|null $gridPosition
     *                                      Column id (or integer position) after which the extra grid column is inserted
     */
    public function __construct(
        public readonly ExtraPropertyType $type = ExtraPropertyType::String,
        public readonly ExtraPropertyScope $scope = ExtraPropertyScope::Common,
        public readonly ?array $enumValues = null,
        public readonly int|float|string|bool|null $defaultValue = null,
        public readonly bool $nullable = true,
        public readonly ?string $moduleName = null,
        public readonly ?string $titleWording = null,
        public readonly ?string $titleDomain = null,
        public readonly ?string $descriptionWording = null,
        public readonly ?string $descriptionDomain = null,
        public readonly string $sqlIndex = 'none',
        public readonly ?string $symfonyFieldType = null,
        public readonly ?string $validator = null,
        public readonly bool $displayFront = false,
        public readonly bool $displayApi = false,
        public readonly bool $displayBo = true,
        public readonly bool $displayGrid = false,
        public readonly ?string $propertyPath = null,
        public readonly string|int|null $gridPosition = null,
    ) {
    }

    /**
     * Creates an ExtraPropertyOptions from a legacy raw options array.
     *
     * Accepts all keys documented on EntityExtraFieldRegistryInterface::register().
     * The 'type' key accepts an ExtraPropertyType instance or a string label (e.g. "string").
     * The 'scope'/'field_scope' key accepts an ExtraPropertyScope instance or a string ('common', 'lang', 'shop').
     *
     * @param array<string, mixed> $options
     *
     * @return self
     */
    public static function fromArray(array $options): self
    {
        $type = ExtraPropertyType::fromRegisterOption($options['type'] ?? null);

        $scopeRaw = $options['scope'] ?? $options['field_scope'] ?? ExtraPropertyScope::Common;
        if ($scopeRaw instanceof ExtraPropertyScope) {
            $scope = $scopeRaw;
        } elseif (is_string($scopeRaw)) {
            $scope = ExtraPropertyScope::tryFrom($scopeRaw) ?? ExtraPropertyScope::Common;
        } else {
            $scope = ExtraPropertyScope::Common;
        }

        $enumValues = (isset($options['enumValues']) && is_array($options['enumValues']))
            ? array_values(array_filter($options['enumValues'], 'is_string'))
            : null;

        return new self(
            type: $type,
            scope: $scope,
            enumValues: $enumValues,
            defaultValue: isset($options['defaultValue']) && is_scalar($options['defaultValue']) ? $options['defaultValue'] : null,
            nullable: !array_key_exists('nullable', $options) || (bool) $options['nullable'],
            moduleName: isset($options['module_name']) && is_string($options['module_name']) ? $options['module_name'] : null,
            titleWording: isset($options['title_wording']) && is_scalar($options['title_wording']) ? (string) $options['title_wording'] : null,
            titleDomain: isset($options['title_domain']) && is_scalar($options['title_domain']) ? (string) $options['title_domain'] : null,
            descriptionWording: isset($options['description_wording']) && is_scalar($options['description_wording']) ? (string) $options['description_wording'] : null,
            descriptionDomain: isset($options['description_domain']) && is_scalar($options['description_domain']) ? (string) $options['description_domain'] : null,
            sqlIndex: isset($options['sql_index']) && is_string($options['sql_index']) ? $options['sql_index'] : 'none',
            symfonyFieldType: isset($options['symfony_field_type']) && is_string($options['symfony_field_type']) ? $options['symfony_field_type'] : null,
            validator: isset($options['validator']) && is_string($options['validator']) ? $options['validator'] : null,
            displayFront: (bool) ($options['display_front'] ?? false),
            displayApi: (bool) ($options['display_api'] ?? false),
            displayBo: !array_key_exists('display_bo', $options) || (bool) $options['display_bo'],
            displayGrid: (bool) ($options['display_grid'] ?? false),
            propertyPath: isset($options['property_path']) && is_string($options['property_path']) ? $options['property_path'] : null,
            gridPosition: isset($options['grid_position']) ? $options['grid_position'] : null,
        );
    }

    /**
     * Converts this VO back to a raw options array compatible with EntityExtraFieldRegistryInterface::register().
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'scope' => $this->scope->value,
            'enumValues' => $this->enumValues,
            'defaultValue' => $this->defaultValue,
            'nullable' => $this->nullable,
            'module_name' => $this->moduleName,
            'title_wording' => $this->titleWording,
            'title_domain' => $this->titleDomain,
            'description_wording' => $this->descriptionWording,
            'description_domain' => $this->descriptionDomain,
            'sql_index' => $this->sqlIndex,
            'symfony_field_type' => $this->symfonyFieldType,
            'validator' => $this->validator,
            'display_front' => $this->displayFront,
            'display_api' => $this->displayApi,
            'display_bo' => $this->displayBo,
            'display_grid' => $this->displayGrid,
            'property_path' => $this->propertyPath,
            'grid_position' => $this->gridPosition,
        ];
    }
}
