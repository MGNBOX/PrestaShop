<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Storage;

/**
 * Writes extra property values for a given entity instance.
 *
 * Used by ObjectModel (via ServiceLocator) when persisting extra property values
 * set via setExtraProperty().
 */
interface ExtraPropertyWriterInterface
{
    /**
     * Persists one extra property value for a given entity instance.
     *
     * Performs an UPSERT (INSERT … ON DUPLICATE KEY UPDATE) on the appropriate
     * *_extra / *_extra_lang / *_extra_shop table.
     *
     * @param string $entityName Entity table name (e.g. "product")
     * @param string $primaryKeyName PK column name (e.g. "id_product")
     * @param int $entityId
     * @param string $storageColumnName Physical column name in the *_extra table
     * @param mixed $value
     * @param string $fieldScope 'common' | 'lang' | 'shop'
     * @param int|null $langId Required for lang scope
     * @param int|null $shopId Required for lang and shop scopes
     *
     * @return bool
     */
    public function writeValue(
        string $entityName,
        string $primaryKeyName,
        int $entityId,
        string $storageColumnName,
        mixed $value,
        string $fieldScope = 'common',
        ?int $langId = null,
        ?int $shopId = null
    ): bool;

    /**
     * Persists all pending extra property values for one entity instance (all scopes).
     *
     * @param string $entityName
     * @param string $primaryKeyName
     * @param int $entityId
     * @param array<string, mixed> $entityValues ['storageColumn' => value] for entity scope
     * @param array<int, array<string, mixed>> $langValuesByIdLang [idLang => ['storageColumn' => value]]
     * @param array<string, mixed> $shopValues ['storageColumn' => value] for shop scope
     * @param int|null $shopId
     */
    public function writeAll(
        string $entityName,
        string $primaryKeyName,
        int $entityId,
        array $entityValues,
        array $langValuesByIdLang,
        array $shopValues,
        ?int $shopId = null
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
