<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Pricing\Product;

use PrestaShop\PrestaShop\Core\Pricing\ValueObject\TaxablePrice;

/**
 * Lightweight ProductPrice DTO with no tracking overhead. Setters simply assign values.
 */
class ProductPrice implements ProductPriceInterface
{
    protected TaxablePrice $unitPrice;
    protected TaxablePrice $originalPrice;

    protected function __construct(
        protected readonly int $productId,
        protected readonly int $combinationId,
        protected readonly int $quantity,
    ) {
        $this->unitPrice = TaxablePrice::zero();
        $this->originalPrice = TaxablePrice::zero();
    }

    public static function create(int $productId, int $combinationId, int $quantity = 1): self
    {
        return new self($productId, $combinationId, $quantity);
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getCombinationId(): int
    {
        return $this->combinationId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitPrice(): TaxablePrice
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(TaxablePrice $unitPrice): void
    {
        $this->unitPrice = $unitPrice;
    }

    public function getOriginalPrice(): TaxablePrice
    {
        return $this->originalPrice;
    }

    public function setOriginalPrice(TaxablePrice $originalPrice): void
    {
        $this->originalPrice = $originalPrice;
    }
}
