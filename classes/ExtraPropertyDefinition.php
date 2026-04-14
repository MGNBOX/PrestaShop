<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

/**
 * ObjectModel mapped on the extra_property_definition registry table.
 *
 * Represents a single declared extra property definition.
 * Used for programmatic read/update of definition flags (display_api, display_form, etc.)
 * via the standard ObjectModel CRUD API.
 */
class ExtraPropertyDefinitionCore extends ObjectModel
{
    /** @var string PrestaShop entity table name */
    public $entity_name;

    /** @var string Storage scope: entity, lang or shop */
    public $scope = 'common';

    /** @var string|null Technical module name owning this field */
    public $module_name;

    /** @var string Technical extra property name */
    public $property_name;

    /** @var string|null Symfony field type used in forms */
    public $form_field_type;

    /** @var string ExtraPropertyType enum label (matches extra_property_definition.type ENUM); DDL on value tables via ColumnDefinitionMapper */
    public $type = 'string';

    /** @var string|null Form placement path (Symfony form path) */
    public $form_position;

    /** @var string SQL index strategy applied on the storage column */
    public $sql_index = 'none';

    /** @var string|null Validation method name */
    public $validator;

    /** @var bool Whether the field is exposed in Admin API */
    public $display_api = false;

    /** @var bool Whether the field is exposed in back office */
    public $display_form = true;

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
            'scope' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 16],
            'module_name' => ['type' => self::TYPE_STRING, 'validate' => 'isModuleName', 'allow_null' => true, 'size' => 64, 'default' => null],
            'property_name' => ['type' => self::TYPE_STRING, 'validate' => 'isTableOrIdentifier', 'required' => true, 'size' => 64],
            'type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 16],
            'form_field_type' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true, 'size' => 255],
            'form_position' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true, 'size' => 255],
            'sql_index' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 16],
            'validator' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true, 'size' => 255],
            'display_api' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'display_form' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'display_grid' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'grid_position' => ['type' => self::TYPE_STRING, 'validate' => 'isTableOrIdentifier', 'allow_null' => true, 'size' => 64],
            'title_wording' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true, 'size' => 191],
            'title_domain' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true, 'size' => 255],
            'description_wording' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true, 'size' => 191],
            'description_domain' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true, 'size' => 255],
        ],
    ];
}
