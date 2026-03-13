<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Domain\ExtraProperty\QueryResult;

/**
 * Read-only value object carrying all metadata for one extra property definition.
 *
 * Produced by GetExtraPropertyDefinitionsHandler and returned as an array
 * by GetExtraPropertyDefinitionsHandlerInterface::handle().
 */
class ExtraPropertyDefinitionInfo
{
    /**
     * @param int $id Definition primary key
     * @param string $entityName Entity table name (e.g. 'product')
     * @param string $moduleName Module technical name ('' for core fields)
     * @param string $fieldName Field name as declared by the module
     * @param string $storageColumnName Physical column name in the extra table
     * @param string $fieldType Type literal matching ExtraPropertyType (e.g. 'string', 'bool')
     * @param string $fieldScope Scope literal matching ExtraPropertyScope ('common', 'lang', 'shop')
     * @param bool $displayFront Whether the field is exposed to front-office templates
     * @param bool $displayApi Whether the field is exposed via the Admin API
     * @param bool $displayBo Whether the field is shown in Back Office forms
     * @param bool $displayGrid Whether the field is shown in Back Office grids
     * @param int|null $gridPosition Column position in BO grids
     * @param string|null $validator Validate:: method name for value validation
     * @param string|null $symfonyFieldType Symfony form type FQCN override
     * @param string|null $propertyPath Dot-notation path to the sub-form in BO forms
     * @param string|null $titleWording i18n wording for the field label
     * @param string|null $titleDomain Translation domain for the field label
     * @param string|null $descriptionWording i18n wording for the field description
     * @param string|null $descriptionDomain Translation domain for the field description
     */
    public function __construct(
        protected readonly int $id,
        protected readonly string $entityName,
        protected readonly string $moduleName,
        protected readonly string $fieldName,
        protected readonly string $storageColumnName,
        protected readonly string $fieldType,
        protected readonly string $fieldScope,
        protected readonly bool $displayFront,
        protected readonly bool $displayApi,
        protected readonly bool $displayBo,
        protected readonly bool $displayGrid,
        protected readonly ?int $gridPosition,
        protected readonly ?string $validator,
        protected readonly ?string $symfonyFieldType,
        protected readonly ?string $propertyPath,
        protected readonly ?string $titleWording,
        protected readonly ?string $titleDomain,
        protected readonly ?string $descriptionWording,
        protected readonly ?string $descriptionDomain,
    ) {
    }

    /**
     * Builds an instance from a raw registry row (as returned by ExtraPropertyDefinitionRepository).
     *
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) ($row['id_extra_property_definition'] ?? 0),
            (string) ($row['entity_name'] ?? ''),
            (string) ($row['module_name'] ?? ''),
            (string) ($row['field_name'] ?? ''),
            (string) ($row['storage_column_name'] ?? ''),
            (string) ($row['field_type'] ?? ''),
            (string) ($row['field_scope'] ?? 'common'),
            !empty($row['display_front']),
            !empty($row['display_api']),
            !empty($row['display_bo']),
            !empty($row['display_grid']),
            isset($row['grid_position']) ? (int) $row['grid_position'] : null,
            isset($row['validator']) && '' !== $row['validator'] ? (string) $row['validator'] : null,
            isset($row['symfony_field_type']) && '' !== $row['symfony_field_type'] ? (string) $row['symfony_field_type'] : null,
            isset($row['property_path']) && '' !== $row['property_path'] ? (string) $row['property_path'] : null,
            isset($row['title_wording']) && '' !== $row['title_wording'] ? (string) $row['title_wording'] : null,
            isset($row['title_domain']) && '' !== $row['title_domain'] ? (string) $row['title_domain'] : null,
            isset($row['description_wording']) && '' !== $row['description_wording'] ? (string) $row['description_wording'] : null,
            isset($row['description_domain']) && '' !== $row['description_domain'] ? (string) $row['description_domain'] : null,
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

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getStorageColumnName(): string
    {
        return $this->storageColumnName;
    }

    public function getFieldType(): string
    {
        return $this->fieldType;
    }

    public function getFieldScope(): string
    {
        return $this->fieldScope;
    }

    public function isDisplayFront(): bool
    {
        return $this->displayFront;
    }

    public function isDisplayApi(): bool
    {
        return $this->displayApi;
    }

    public function isDisplayBo(): bool
    {
        return $this->displayBo;
    }

    public function isDisplayGrid(): bool
    {
        return $this->displayGrid;
    }

    public function getGridPosition(): ?int
    {
        return $this->gridPosition;
    }

    public function getValidator(): ?string
    {
        return $this->validator;
    }

    public function getSymfonyFieldType(): ?string
    {
        return $this->symfonyFieldType;
    }

    public function getPropertyPath(): ?string
    {
        return $this->propertyPath;
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
