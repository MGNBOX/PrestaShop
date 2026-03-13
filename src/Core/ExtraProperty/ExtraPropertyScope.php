<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty;

/**
 * Storage scope for extra property fields.
 *
 * String values match the field_scope ENUM column in extra_property_definition for DB compatibility.
 * Case names follow PrestaShop's PR convention (Common = entity-level, Lang = per-language, Shop = per-shop).
 */
enum ExtraPropertyScope: string
{
    /** Stored in {entity}_extra — one row per entity row, not shop/lang dependent */
    case Common = 'common';

    /** Stored in {entity}_extra_lang — one row per entity × lang × shop */
    case Lang = 'lang';

    /** Stored in {entity}_extra_shop — one row per entity × shop */
    case Shop = 'shop';

    /**
     * Returns all enum raw values.
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
}
