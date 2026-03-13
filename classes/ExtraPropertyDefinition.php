<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

/**
 * ObjectModel mapped on the extra_property_definition registry table.
 *
 * Represents a single declared extra property definition.
 * Used for programmatic read/update of definition flags (display_front, display_api, display_bo, etc.)
 * via the standard ObjectModel CRUD API.
 */
class ExtraPropertyDefinitionCore extends ObjectModel
{
    /** @var string PrestaShop entity table name */
    public $entity_name;

    /** @var string Storage scope: entity, lang or shop */
    public $field_scope = 'common';

    /** @var string|null Technical module name owning this field */
    public $module_name;

    /** @var string Technical extra field name */
    public $field_name;

    /** @var string Physical SQL column name used in *_extra tables */
    public $storage_column_name;

    /** @var string|null Symfony field type used in forms */
    public $symfony_field_type;

    /** @var string ExtraPropertyType enum label (matches extra_property_definition.field_type ENUM); DDL on value tables via ColumnDefinitionMapper */
    public $field_type = 'string';

    /** @var string|null Form placement path (Symfony form path) */
    public $property_path;

    /** @var string SQL index strategy applied on the storage column */
    public $sql_index = 'none';

    /** @var string|null Validation method name */
    public $validator;

    /** @var bool Whether the field is exposed in front office */
    public $display_front = false;

    /** @var bool Whether the field is exposed in Admin API */
    public $display_api = false;

    /** @var bool Whether the field is exposed in back office */
    public $display_bo = true;

    /** @var bool Whether the field is exposed in back office grids */
    public $display_grid = false;

    /** @var string|null Position reference in BO grids (column id) */
    public $grid_position;

    /** @var string|null Translation wording key for BO title */
    public $title_wording;

    /** @var string|null Translation domain for BO title */
    public $title_domain;

    /** @var string|null Translation wording key for BO help/description */
    public $description_wording;

    /** @var string|null Translation domain for BO help/description */
    public $description_domain;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'extra_property_definition',
        'primary' => 'id_extra_property_definition',
        'multilang' => false,
        'fields' => [
            'entity_name' => ['type' => self::TYPE_STRING, 'validate' => 'isTableOrIdentifier', 'required' => true, 'size' => 64],
            'field_scope' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 16],
            'module_name' => ['type' => self::TYPE_STRING, 'validate' => 'isModuleName', 'allow_null' => true, 'size' => 64],
            'field_name' => ['type' => self::TYPE_STRING, 'validate' => 'isTableOrIdentifier', 'required' => true, 'size' => 64],
            'storage_column_name' => ['type' => self::TYPE_STRING, 'validate' => 'isTableOrIdentifier', 'required' => true, 'size' => 64],
            'field_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 16],
            'symfony_field_type' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true, 'size' => 255],
            'property_path' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true, 'size' => 255],
            'sql_index' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 16],
            'validator' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true, 'size' => 255],
            'display_front' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'display_api' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'display_bo' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'display_grid' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'grid_position' => ['type' => self::TYPE_STRING, 'validate' => 'isTableOrIdentifier', 'allow_null' => true, 'size' => 64],
            'title_wording' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true, 'size' => 191],
            'title_domain' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true, 'size' => 255],
            'description_wording' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true, 'size' => 191],
            'description_domain' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true, 'size' => 255],
        ],
    ];
}
