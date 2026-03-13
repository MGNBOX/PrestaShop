<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Schema;

use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyType;

/**
 * Maps an ExtraPropertyType enum case to a complete SQL column definition fragment.
 *
 * The returned string is ready to be appended after the column name in an ALTER TABLE … ADD COLUMN statement.
 * NULL/NOT NULL and DEFAULT clauses are always explicit so the caller does not need to add them.
 *
 * Supported options (passed as array until ExtraPropertyOptions VO is introduced in step 3):
 *   - enumValues  (list<string>): if provided with Choice type, generates ENUM('v1','v2') instead of VARCHAR(64).
 *   - defaultValue (scalar|null): if provided, appends a DEFAULT clause with proper quoting per type.
 *   - nullable     (bool, default true): controls NULL vs NOT NULL.
 */
class ColumnDefinitionMapper
{
    /**
     * Returns the full SQL column definition fragment for the given type and options.
     *
     * @param ExtraPropertyType $type
     * @param array<string, mixed> $options Supported: enumValues (list<string>), defaultValue (scalar), nullable (bool)
     *
     * @return string e.g. "VARCHAR(255) NULL" or "ENUM('a','b') NOT NULL DEFAULT 'a'"
     */
    public static function getSqlDefinition(ExtraPropertyType $type, array $options = []): string
    {
        $enumValues = (isset($options['enumValues']) && is_array($options['enumValues']))
            ? array_values(array_filter($options['enumValues'], 'is_string'))
            : [];

        $nullable = !array_key_exists('nullable', $options) || (bool) $options['nullable'];
        $defaultValue = array_key_exists('defaultValue', $options) ? $options['defaultValue'] : null;

        $baseDefinition = self::buildBaseDefinition($type, $enumValues);
        $nullClause = $nullable ? 'NULL' : 'NOT NULL';

        $parts = [$baseDefinition, $nullClause];

        if (null !== $defaultValue) {
            $parts[] = 'DEFAULT ' . self::quoteDefaultValue($type, $defaultValue);
        }

        return implode(' ', $parts);
    }

    /**
     * Returns the base SQL type string (without NULL/NOT NULL or DEFAULT).
     *
     * @param ExtraPropertyType $type
     * @param list<string> $enumValues Only used for Choice type
     *
     * @return string
     */
    private static function buildBaseDefinition(ExtraPropertyType $type, array $enumValues): string
    {
        return match ($type) {
            ExtraPropertyType::Int => 'INT(11)',
            ExtraPropertyType::Bool => 'TINYINT(1) UNSIGNED',
            ExtraPropertyType::String => 'VARCHAR(255)',
            ExtraPropertyType::Float => 'DECIMAL(20,6)',
            ExtraPropertyType::Date => 'DATETIME',
            ExtraPropertyType::Html => 'TEXT',
            ExtraPropertyType::Json => 'LONGTEXT',
            ExtraPropertyType::Choice => !empty($enumValues) ? self::buildEnumDefinition($enumValues) : 'VARCHAR(64)',
        };
    }

    /**
     * Builds an ENUM SQL definition from a list of allowed values, with proper single-quote escaping.
     *
     * @param list<string> $enumValues
     *
     * @return string e.g. "ENUM('pending','active','closed')"
     */
    private static function buildEnumDefinition(array $enumValues): string
    {
        $quotedValues = array_map(
            static fn (string $v): string => "'" . str_replace("'", "''", $v) . "'",
            $enumValues
        );

        return 'ENUM(' . implode(',', $quotedValues) . ')';
    }

    /**
     * Formats a default value as a SQL literal appropriate for the given type.
     *
     * Numeric types are unquoted; string-like types are single-quoted with escaping.
     *
     * @param ExtraPropertyType $type
     * @param mixed $defaultValue
     *
     * @return string
     */
    private static function quoteDefaultValue(ExtraPropertyType $type, mixed $defaultValue): string
    {
        return match ($type) {
            ExtraPropertyType::Int,
            ExtraPropertyType::Bool,
            ExtraPropertyType::Float => (string) $defaultValue,
            ExtraPropertyType::String,
            ExtraPropertyType::Date,
            ExtraPropertyType::Html,
            ExtraPropertyType::Json,
            ExtraPropertyType::Choice => "'" . str_replace("'", "''", (string) $defaultValue) . "'",
        };
    }
}
