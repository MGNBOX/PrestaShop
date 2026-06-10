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
     * Persists all extra property values for one entity instance (all scopes in one call).
     *
     * All three value arrays may be empty; non-empty arrays trigger an UPSERT on the
     * corresponding *_extra / *_extra_lang / *_extra_shop table.
     *
     * Lang and shop writes require a specific shop: use ShopConstraint::shop($id).
     * ShopConstraint::allShops() leaves lang and shop-scope values unwritten (use the caller
     * to iterate shops when broad writes are needed).
     *
     * @param string $entityName Entity table name (e.g. "product")
     * @param string $primaryKeyName PK column name (e.g. "id_product")
     * @param int $entityId
     * @param array<string, mixed> $entityValues ['storageColumn' => value] for common scope
     * @param array<int, array<string, mixed>> $langValuesByIdLang [idLang => ['storageColumn' => value]]
     * @param array<string, mixed> $shopValues ['storageColumn' => value] for shop scope
     * @param ShopConstraint $shopConstraint Specific shop for lang/shop scopes; allShops() skips them
     */
    public function writeAll(
        string $entityName,
        string $primaryKeyName,
        int $entityId,
        array $entityValues,
        array $langValuesByIdLang,
        array $shopValues,
        ShopConstraint $shopConstraint
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
