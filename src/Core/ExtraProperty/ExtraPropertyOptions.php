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
 * Pass an instance of this class to Module::registerExtraProperty() to configure the field.
 * All properties are public readonly to allow direct access without getters, and to ensure
 * immutability. Use withModuleName() to derive a copy with a resolved module name.
 *
 * About BO label translations:
 * - title/description are not stored as per-language values in SQL;
 * - use wording + domain pairs (titleWording/titleDomain and descriptionWording/descriptionDomain);
 * - BO rendering translates them at runtime with Translator::trans();
 * - for BO translation pages to discover those strings, modules must expose the same wordings through
 *   explicit $this->trans('...', [], 'Modules.<Module>.Admin') calls (and/or module XLF files).
 *
 * @see \PrestaShop\PrestaShop\Core\ExtraProperty\Registry\ExtraPropertyRegistryInterface::register()
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
     *                                  If provided, adds a DEFAULT clause in the DDL, quoted according to field type.
     *                                  Also persisted in the registry so the configured default is always retrievable.
     * @param bool $nullable
     *                       Controls NULL vs NOT NULL in the DDL
     * @param bool $formRequired
     *                           When true, marks the BO form field as required (HTML required + Symfony NotBlank constraint).
     *                           Independent of $nullable: a field can be NOT NULL with a default and still be optional in the form.
     * @param int|null $size
     *                       For ExtraPropertyType::String: the varchar column length (1–16383).
     *                       Defaults to 255 when null. Ignored for all other types.
     * @param string|null $moduleName
     *                                Override the owning module name. Null means use the calling module's name.
     *                                Automatically populated by Module::registerExtraProperty() when left null.
     * @param string|null $titleWording
     *                                  Translation wording key shown in BO forms. Example: "Theme color".
     * @param string|null $titleDomain
     *                                 Translation domain used for the title wording. Example: "Modules.MyModule.Admin".
     * @param string|null $descriptionWording
     *                                        Translation wording key shown as BO help text
     * @param string|null $descriptionDomain
     *                                       Translation domain used for the description wording
     * @param ExtraPropertySqlIndex $sqlIndex
     *                                        SQL index strategy on the storage column
     * @param string|null $formFieldType
     *                                   Fully-qualified Symfony Form type class name used by the BO form renderer.
     *                                   When null, the default mapping from ExtraPropertyType is applied.
     * @param array<string, mixed>|null $formOptions
     *                                               Extra options passed verbatim to the Symfony form type constructor.
     *                                               Merged with the automatically-resolved options; developer-supplied values win.
     * @param string|null $validator
     *                               PrestaShop Validate method name (e.g. "isUrl", "isBool") applied before persistence.
     * @param bool $displayApi
     *                         Include this field in Admin API JSON responses
     * @param bool $displayForm
     *                          Show and edit this field in BO forms
     * @param bool $displayGrid
     *                          Display this field as a column in BO Symfony grids
     * @param string|null $formPosition
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
        public readonly bool $formRequired = false,
        public readonly ?int $size = null,
        public readonly ?string $moduleName = null,
        public readonly ?string $titleWording = null,
        public readonly ?string $titleDomain = null,
        public readonly ?string $descriptionWording = null,
        public readonly ?string $descriptionDomain = null,
        public readonly ExtraPropertySqlIndex $sqlIndex = ExtraPropertySqlIndex::None,
        public readonly ?string $formFieldType = null,
        public readonly ?array $formOptions = null,
        public readonly ?string $validator = null,
        public readonly bool $displayApi = false,
        public readonly bool $displayForm = true,
        public readonly bool $displayGrid = false,
        public readonly ?string $formPosition = null,
        public readonly string|int|null $gridPosition = null,
    ) {
    }

    /**
     * Returns a copy of this options object with the given module name set.
     *
     * Used by Module::registerExtraProperty() to inject the calling module's name
     * when moduleName was left null by the developer.
     *
     * @param string $moduleName
     *
     * @return self
     */
    public function withModuleName(string $moduleName): self
    {
        return new self(
            type: $this->type,
            scope: $this->scope,
            enumValues: $this->enumValues,
            defaultValue: $this->defaultValue,
            nullable: $this->nullable,
            formRequired: $this->formRequired,
            size: $this->size,
            moduleName: $moduleName,
            titleWording: $this->titleWording,
            titleDomain: $this->titleDomain,
            descriptionWording: $this->descriptionWording,
            descriptionDomain: $this->descriptionDomain,
            sqlIndex: $this->sqlIndex,
            formFieldType: $this->formFieldType,
            formOptions: $this->formOptions,
            validator: $this->validator,
            displayApi: $this->displayApi,
            displayForm: $this->displayForm,
            displayGrid: $this->displayGrid,
            formPosition: $this->formPosition,
            gridPosition: $this->gridPosition,
        );
    }
}
