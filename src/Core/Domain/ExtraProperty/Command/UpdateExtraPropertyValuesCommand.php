<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Domain\ExtraProperty\Command;

/**
 * Command to persist extra property values for a single entity instance.
 *
 * Carries all three scope payloads in one command so the handler can batch
 * the writes efficiently:
 *  - entityValues       : common scope   — one row per entity
 *  - langValuesByIdLang : lang scope     — one row per entity × lang
 *  - shopValuesByShopId : shop scope     — one row per entity × shop
 *
 * The command does not perform any validation; the handler is responsible for
 * delegating to ExtraPropertyWriterInterface which enforces DB constraints.
 */
class UpdateExtraPropertyValuesCommand
{
    /**
     * @param string $entityName Entity table name (e.g. 'product')
     * @param string $primaryKeyName PK column name (e.g. 'id_product')
     * @param int $entityId Entity primary key value
     * @param array<string, mixed> $entityValues Common-scope values: [storage_column => value]
     * @param array<int, array<string, mixed>> $langValuesByIdLang Lang-scope values: [id_lang => [storage_column => value]]
     * @param array<int, array<string, mixed>> $shopValuesByShopId Shop-scope values: [id_shop => [storage_column => value]]
     * @param int|null $langShopId Shop context used when writing lang-scope values in multishop mode
     */
    public function __construct(
        protected readonly string $entityName,
        protected readonly string $primaryKeyName,
        protected readonly int $entityId,
        protected readonly array $entityValues,
        protected readonly array $langValuesByIdLang,
        protected readonly array $shopValuesByShopId,
        protected readonly ?int $langShopId = null,
    ) {
    }

    /**
     * Returns the entity table name (e.g. 'product').
     */
    public function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * Returns the primary key column name (e.g. 'id_product').
     */
    public function getPrimaryKeyName(): string
    {
        return $this->primaryKeyName;
    }

    /**
     * Returns the entity primary key value.
     */
    public function getEntityId(): int
    {
        return $this->entityId;
    }

    /**
     * Returns common-scope values indexed by storage column name.
     *
     * @return array<string, mixed>
     */
    public function getEntityValues(): array
    {
        return $this->entityValues;
    }

    /**
     * Returns lang-scope values grouped by id_lang, each sub-array indexed by storage column name.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLangValuesByIdLang(): array
    {
        return $this->langValuesByIdLang;
    }

    /**
     * Returns shop-scope values grouped by id_shop, each sub-array indexed by storage column name.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getShopValuesByShopId(): array
    {
        return $this->shopValuesByShopId;
    }

    /**
     * Returns the shop context used when persisting lang-scope values in multishop mode.
     */
    public function getLangShopId(): ?int
    {
        return $this->langShopId;
    }
}
