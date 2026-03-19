<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Pricing\Product\Calculator;

use PrestaShop\PrestaShop\Core\Pricing\Product\ProductPriceInterface;
use PrestaShop\PrestaShop\Core\Pricing\Product\Provider\ProductProviderInterface;
use PrestaShop\PrestaShop\Core\Pricing\ValueObject\TaxablePrice;
use PrestaShop\PrestaShop\Core\Pricing\ValueObject\TaxRate;

/**
 * First calculator in the pipeline: fetches the base product price from the provider
 * and applies the combination price impact when relevant.
 */
class BaseProductCalculator implements ProductCalculatorInterface
{
    public function __construct(
        protected readonly ProductProviderInterface $productProvider,
    ) {
    }

    public function compute(ProductPriceInterface $productPrice): void
    {
        $basePrice = $this->productProvider->getBasePrice($productPrice->getProductId());
        $productPrice->setUnitPrice(new TaxablePrice($basePrice, TaxRate::zero()));
        $productPrice->setOriginalPrice(new TaxablePrice($basePrice, TaxRate::zero()));

        if ($productPrice->getCombinationId() > 0) {
            $impact = $this->productProvider->getCombinationPriceImpact(
                $productPrice->getProductId(),
                $productPrice->getCombinationId()
            );
            $combinationImpactPrice = new TaxablePrice($impact, TaxRate::zero());
            $unitPrice = new TaxablePrice($basePrice, TaxRate::zero());
            $unitPrice->plus($combinationImpactPrice);
            $productPrice->setUnitPrice($unitPrice);
        }
    }
}
