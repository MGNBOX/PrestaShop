<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Domain\ExtraProperty\QueryResult;

use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyNaming;

/**
 * Read-only value object carrying all metadata for one extra property definition.
 */
class ExtraPropertyDefinitionInfo
{
    /**
     * @param int $id Definition primary key
     * @param string $entityName Entity table name (e.g. 'product')
     * @param string $moduleName Module technical name ('' for core fields)
     * @param string $fieldName Field name as declared by the module
     * @param string $fieldType Type literal matching ExtraPropertyType (e.g. 'string', 'bool')
     * @param string $fieldScope Scope literal matching ExtraPropertyScope ('common', 'lang', 'shop')
     * @param bool $displayApi Whether the field is exposed via the Admin API
     * @param bool $displayForm Whether the field is shown in Back Office forms
     * @param bool $displayGrid Whether the field is shown in Back Office grids
     * @param string|null $gridPosition Column ID after which the extra column is inserted in BO grids (e.g. "reference")
     * @param string|null $validator Validate:: method name for value validation
     * @param string|null $formFieldType Symfony form type FQCN override
     * @param array<string, mixed>|null $formOptions Extra options merged into the Symfony form type constructor
     * @param string|null $formPosition Dot-notation path to the sub-form in BO forms
     * @param string|null $titleWording i18n wording for the field label
     * @param string|null $titleDomain Translation domain for the field label
     * @param string|null $descriptionWording i18n wording for the field description
     * @param string|null $descriptionDomain Translation domain for the field description
     * @param int|null $size For string type: varchar column size (defaults to 255 when null)
     * @param bool $formRequired Whether the BO form field is required (HTML required + Symfony NotBlank)
     * @param string|null $defaultValue SQL DEFAULT clause value as stored in the registry
     */
    public function __construct(
        protected readonly int $id,
        protected readonly string $entityName,
        protected readonly string $moduleName,
        protected readonly string $propertyName,
        protected readonly string $fieldType,
        protected readonly string $fieldScope,
        protected readonly bool $displayApi,
        protected readonly bool $displayForm,
        protected readonly bool $displayGrid,
        protected readonly ?string $gridPosition,
        protected readonly ?string $validator,
        protected readonly ?string $formFieldType,
        protected readonly ?array $formOptions,
        protected readonly ?string $formPosition,
        protected readonly ?string $titleWording,
        protected readonly ?string $titleDomain,
        protected readonly ?string $descriptionWording,
        protected readonly ?string $descriptionDomain,
        protected readonly ?int $size = null,
        protected readonly bool $formRequired = false,
        protected readonly ?string $defaultValue = null,
    ) {
    }

    /**
     * Builds an instance from a raw registry row (as returned by ExtraPropertyDefinitionRepository).
     *
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $formOptionsRaw = $row['form_options'] ?? null;
        $formOptions = (is_string($formOptionsRaw) && '' !== $formOptionsRaw)
            ? json_decode($formOptionsRaw, true)
            : null;

        return new self(
            (int) ($row['id_extra_property_definition'] ?? 0),
            (string) ($row['entity_name'] ?? ''),
            (string) ($row['module_name'] ?? ''),
            (string) ($row['property_name'] ?? ''),
            (string) ($row['type'] ?? ''),
            (string) ($row['scope'] ?? 'common'),
            !empty($row['display_api']),
            !empty($row['display_form']),
            !empty($row['display_grid']),
            isset($row['grid_position']) && '' !== $row['grid_position'] ? (string) $row['grid_position'] : null,
            isset($row['validator']) && '' !== $row['validator'] ? (string) $row['validator'] : null,
            isset($row['form_field_type']) && '' !== $row['form_field_type'] ? (string) $row['form_field_type'] : null,
            is_array($formOptions) ? $formOptions : null,
            isset($row['form_position']) && '' !== $row['form_position'] ? (string) $row['form_position'] : null,
            isset($row['title_wording']) && '' !== $row['title_wording'] ? (string) $row['title_wording'] : null,
            isset($row['title_domain']) && '' !== $row['title_domain'] ? (string) $row['title_domain'] : null,
            isset($row['description_wording']) && '' !== $row['description_wording'] ? (string) $row['description_wording'] : null,
            isset($row['description_domain']) && '' !== $row['description_domain'] ? (string) $row['description_domain'] : null,
            isset($row['size']) && '' !== $row['size'] ? (int) $row['size'] : null,
            !empty($row['form_required']),
            isset($row['default_value']) && '' !== $row['default_value'] ? (string) $row['default_value'] : null,
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function getStorageColumnName(): string
    {
        return ExtraPropertyNaming::storageColumnName($this->moduleName, $this->propertyName);
    }

    public function getFieldType(): string
    {
        return $this->fieldType;
    }

    public function getFieldScope(): string
    {
        return $this->fieldScope;
    }

    public function isDisplayApi(): bool
    {
        return $this->displayApi;
    }

    public function isDisplayForm(): bool
    {
        return $this->displayForm;
    }

    public function isDisplayGrid(): bool
    {
        return $this->displayGrid;
    }

    public function getGridPosition(): ?string
    {
        return $this->gridPosition;
    }

    public function getValidator(): ?string
    {
        return $this->validator;
    }

    public function getFormFieldType(): ?string
    {
        return $this->formFieldType;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getFormOptions(): ?array
    {
        return $this->formOptions;
    }

    public function getFormPosition(): ?string
    {
        return $this->formPosition;
    }

    /**
     * Returns the varchar column length for string-type fields (defaults to 255 when null).
     * Null for all other types (ignored by ColumnDefinitionMapper).
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * Returns true when the BO form field should be marked as required.
     */
    public function isFormRequired(): bool
    {
        return $this->formRequired;
    }

    /**
     * Returns the SQL DEFAULT clause value as stored in the registry, or null when no default was declared.
     */
    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function getTitleWording(): ?string
    {
        return $this->titleWording;
    }

    public function getTitleDomain(): ?string
    {
        return $this->titleDomain;
    }

    public function getDescriptionWording(): ?string
    {
        return $this->descriptionWording;
    }

    public function getDescriptionDomain(): ?string
    {
        return $this->descriptionDomain;
    }
}
