<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty;

/**
 * Supported types for extra property fields.
 *
 * The label string is stored in extra_property_definition.field_type as a MySQL ENUM
 * (same literals as this backed enum). The physical column DDL on value tables is computed
 * at runtime by ColumnDefinitionMapper, so no SQL fragment is persisted in the registry row.
 */
enum ExtraPropertyType: string
{
    case Int = 'int';
    case Bool = 'bool';
    case String = 'string';
    case Float = 'float';
    case Date = 'date';
    case Html = 'html';
    case Json = 'json';
    case Choice = 'choice';

    /**
     * Returns all enum raw values (MySQL ENUM literals).
     *
     * @return string[]
     */
    public static function values(): array
    {
        static $values = null;
        if (null === $values) {
            $values = array_column(self::cases(), 'value');
        }

        return $values;
    }

    /**
     * Resolves a type from register() options: enum instance or string label (ENUM literal).
     *
     * @param mixed $value
     *
     * @return self
     */
    public static function fromRegisterOption(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }
        if (is_string($value)) {
            return self::tryFrom($value) ?? self::String;
        }

        return self::String;
    }
}
