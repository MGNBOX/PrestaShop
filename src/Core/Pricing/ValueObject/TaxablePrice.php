<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Pricing\ValueObject;

use PrestaShop\Decimal\DecimalNumber;
use PrestaShop\PrestaShop\Core\Pricing\Exception\TaxRateMismatchException;
use PrestaShop\PrestaShop\Core\Pricing\PricingConstants;

/**
 * Mutable price that automatically keeps tax-excluded, tax-included and tax-amount in sync
 * through its associated TaxRate. Supports arithmetic operations between prices sharing the
 * same tax rate.
 */
class TaxablePrice
{
    protected DecimalNumber $taxExcluded;
    protected DecimalNumber $taxIncluded;
    protected DecimalNumber $taxAmount;
    protected TaxRate $taxRate;

    /**
     * Primary constructor: derives taxIncluded from taxExcluded * taxRate multiplier.
     */
    public function __construct(DecimalNumber $taxExcluded, TaxRate $taxRate)
    {
        $this->taxExcluded = $taxExcluded;
        $this->taxRate = $taxRate;
        $this->syncFromTaxExcluded();
    }

    /**
     * Reverse constructor: derives taxExcluded from taxIncluded / taxRate multiplier.
     */
    public static function fromTaxIncluded(DecimalNumber $taxIncluded, TaxRate $taxRate): self
    {
        $taxExcluded = $taxIncluded->dividedBy($taxRate->getMultiplier(), PricingConstants::INTERMEDIATE_PRECISION);
        $instance = new self($taxExcluded, $taxRate);
        // Override taxIncluded with the exact value provided
        $instance->taxIncluded = $taxIncluded;
        $instance->taxAmount = $taxIncluded->minus($taxExcluded);

        return $instance;
    }

    public static function zero(): self
    {
        return new self(new DecimalNumber('0'), TaxRate::zero());
    }

    public function getTaxExcluded(): DecimalNumber
    {
        return $this->taxExcluded;
    }

    public function getTaxIncluded(): DecimalNumber
    {
        return $this->taxIncluded;
    }

    public function getTaxAmount(): DecimalNumber
    {
        return $this->taxAmount;
    }

    public function getTaxRate(): TaxRate
    {
        return $this->taxRate;
    }

    /**
     * Sets tax-excluded and recomputes tax-included and tax amount.
     */
    public function setTaxExcluded(DecimalNumber $taxExcluded): void
    {
        $this->taxExcluded = $taxExcluded;
        $this->syncFromTaxExcluded();
    }

    /**
     * Sets tax-included and recomputes tax-excluded and tax amount.
     */
    public function setTaxIncluded(DecimalNumber $taxIncluded): void
    {
        $this->taxIncluded = $taxIncluded;
        $this->taxExcluded = $taxIncluded->dividedBy($this->taxRate->getMultiplier(), PricingConstants::INTERMEDIATE_PRECISION);
        $this->taxAmount = $taxIncluded->minus($this->taxExcluded);
    }

    /**
     * Sets the tax rate and recomputes tax-included and tax amount from tax-excluded (source of truth).
     */
    public function setTaxRate(TaxRate $taxRate): void
    {
        $this->taxRate = $taxRate;
        $this->syncFromTaxExcluded();
    }

    /**
     * Computes the tax amount for this price: taxExcluded * rate / 100.
     */
    public function computeTaxAmount(): DecimalNumber
    {
        return $this->taxExcluded->times($this->taxRate->getRate())
            ->dividedBy(new DecimalNumber('100'), PricingConstants::INTERMEDIATE_PRECISION);
    }

    /**
     * Adds another TaxablePrice to this one. Both must share the same tax rate.
     *
     * @throws TaxRateMismatchException when tax rates differ
     */
    public function plus(self $other): void
    {
        $this->assertSameTaxRate($other);
        $this->taxExcluded = $this->taxExcluded->plus($other->taxExcluded);
        $this->syncFromTaxExcluded();
    }

    /**
     * Subtracts another TaxablePrice from this one. Both must share the same tax rate.
     *
     * @throws TaxRateMismatchException when tax rates differ
     */
    public function minus(self $other): void
    {
        $this->assertSameTaxRate($other);
        $this->taxExcluded = $this->taxExcluded->minus($other->taxExcluded);
        $this->syncFromTaxExcluded();
    }

    /**
     * Multiplies this price by a scalar value and resyncs all internal values.
     */
    public function times(DecimalNumber $factor): void
    {
        $this->taxExcluded = $this->taxExcluded->times($factor);
        $this->syncFromTaxExcluded();
    }

    /**
     * Divides this price by a scalar value and resyncs all internal values.
     */
    public function dividedBy(DecimalNumber $divisor): void
    {
        $this->taxExcluded = $this->taxExcluded->dividedBy($divisor, PricingConstants::INTERMEDIATE_PRECISION);
        $this->syncFromTaxExcluded();
    }

    /**
     * Recomputes taxIncluded and taxAmount from taxExcluded (source of truth).
     */
    protected function syncFromTaxExcluded(): void
    {
        $this->taxAmount = $this->taxExcluded->times($this->taxRate->getRate())
            ->dividedBy(new DecimalNumber('100'), PricingConstants::INTERMEDIATE_PRECISION);
        $this->taxIncluded = $this->taxExcluded->times($this->taxRate->getMultiplier());
    }

    /**
     * @throws TaxRateMismatchException when the other price has a different tax rate
     */
    protected function assertSameTaxRate(self $other): void
    {
        if (!$this->taxRate->equals($other->taxRate)) {
            throw new TaxRateMismatchException(sprintf(
                'Cannot combine TaxablePrice instances with different tax rates (%s%% vs %s%%)',
                $this->taxRate->getRate(),
                $other->taxRate->getRate()
            ));
        }
    }
}
