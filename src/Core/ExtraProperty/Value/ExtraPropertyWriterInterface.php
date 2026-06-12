<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Value;

use InvalidArgumentException;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;
use PrestaShop\PrestaShop\Core\ExtraProperty\Definition\ExtraPropertyDefinition;

/**
 * Writes extra property values for a given entity instance.
 *
 * Used by ObjectModel (via ServiceLocator) when persisting extra property values,
 * and by the BackOffice form persister and Admin API for bulk writes.
 */
interface ExtraPropertyWriterInterface
{
    /**
     * Persists extra property values for one entity instance (all scopes in one call).
     *
     * Values are grouped the same way the reader returns them — by module then property
     * name — and the writer routes each value to the table matching the property's
     * registered scope (storage column names stay internal to the writer):
     *
     * [
     *     'module_technical_name' => [
     *         'common_property' => 'value',
     *         'lang_property' => [1 => 'en value', 2 => 'fr value'],   // or scalar, see $defaultLangId
     *         'shop_property' => 'value',                              // for the constraint's shop
     *     ],
     * ]
     *
     * NULL values are persisted as-is for nullable storage columns and skipped for
     * NOT NULL ones (SQL default applies). Modules/properties without a matching
     * definition are ignored.
     *
     * Lang and shop writes require a specific shop: use ShopConstraint::shop($id).
     * ShopConstraint::allShops() leaves lang and shop-scope values unwritten (the caller
     * iterates shops when broad writes are needed).
     *
     * @param string $entityName Entity table name (e.g. "product")
     * @param string $primaryKeyName PK column name (e.g. "id_product")
     * @param int $entityId
     * @param array<string, array<string, mixed>> $valuesByModule [moduleKey => [propertyName => value]]
     * @param ShopConstraint $shopConstraint Specific shop for lang/shop scopes; allShops() skips them
     * @param int|null $defaultLangId Language used when a lang-scoped value is a scalar; null skips scalar lang values
     */
    public function writeAll(
        string $entityName,
        string $primaryKeyName,
        int $entityId,
        array $valuesByModule,
        ShopConstraint $shopConstraint,
        ?int $defaultLangId = null
    ): void;

    /**
     * Toggles a boolean extra property value for one entity instance.
     *
     * Performs an UPSERT that flips the stored value
     * Only BOOL-typed definitions are accepted; a non-BOOL definition throws \InvalidArgumentException.
     *
     * @param ExtraPropertyDefinition $definition The boolean property to toggle
     * @param string $primaryKeyName PK column name (e.g. "id_product")
     * @param int $entityId
     * @param int $shopId Required when the definition scope is SHOP; ignored otherwise
     *
     * @throws InvalidArgumentException when definition type is not BOOL
     */
    public function toggleExtraProperty(
        ExtraPropertyDefinition $definition,
        string $primaryKeyName,
        int $entityId,
        int $shopId = 0,
    ): void;

    /**
     * Deletes all extra property rows for one entity instance (all three scopes).
     *
     * Safe to call even if no extra properties are registered: tables that do not
     * exist yet are silently skipped.
     *
     * @param string $entityName Entity table name (e.g. "product")
     * @param string $primaryKeyName PK column name (e.g. "id_product")
     * @param int $entityId
     */
    public function deleteAll(string $entityName, string $primaryKeyName, int $entityId): void;
}
