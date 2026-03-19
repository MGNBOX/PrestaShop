<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Unit\Core\Pricing\ValueObject;

use PHPUnit\Framework\TestCase;
use PrestaShop\Decimal\DecimalNumber;
use PrestaShop\PrestaShop\Core\Pricing\Exception\TaxRateMismatchException;
use PrestaShop\PrestaShop\Core\Pricing\ValueObject\TaxablePrice;
use PrestaShop\PrestaShop\Core\Pricing\ValueObject\TaxRate;

class TaxablePriceTest extends TestCase
{
    public function testConstructionAutoSyncs(): void
    {
        $price = new TaxablePrice(new DecimalNumber('100'), new TaxRate(new DecimalNumber('20')));

        $this->assertTrue($price->getTaxExcluded()->equals(new DecimalNumber('100')));
        $this->assertTrue($price->getTaxIncluded()->equals(new DecimalNumber('120')));
        $this->assertTrue($price->getTaxAmount()->equals(new DecimalNumber('20')));
    }

    public function testFromTaxIncluded(): void
    {
        $price = TaxablePrice::fromTaxIncluded(new DecimalNumber('120'), new TaxRate(new DecimalNumber('20')));

        $this->assertTrue($price->getTaxExcluded()->equals(new DecimalNumber('100')));
        $this->assertTrue($price->getTaxIncluded()->equals(new DecimalNumber('120')));
        $this->assertTrue($price->getTaxAmount()->equals(new DecimalNumber('20')));
    }

    public function testZero(): void
    {
        $price = TaxablePrice::zero();

        $this->assertTrue($price->getTaxExcluded()->equalsZero());
        $this->assertTrue($price->getTaxIncluded()->equalsZero());
        $this->assertTrue($price->getTaxAmount()->equalsZero());
        $this->assertTrue($price->getTaxRate()->getRate()->equalsZero());
    }

    public function testSetTaxExcludedRecomputes(): void
    {
        $price = new TaxablePrice(new DecimalNumber('100'), new TaxRate(new DecimalNumber('20')));
        $price->setTaxExcluded(new DecimalNumber('200'));

        $this->assertTrue($price->getTaxExcluded()->equals(new DecimalNumber('200')));
        $this->assertTrue($price->getTaxIncluded()->equals(new DecimalNumber('240')));
        $this->assertTrue($price->getTaxAmount()->equals(new DecimalNumber('40')));
    }

    public function testSetTaxIncludedRecomputes(): void
    {
        $price = new TaxablePrice(new DecimalNumber('100'), new TaxRate(new DecimalNumber('20')));
        $price->setTaxIncluded(new DecimalNumber('240'));

        $this->assertTrue($price->getTaxExcluded()->equals(new DecimalNumber('200')));
        $this->assertTrue($price->getTaxIncluded()->equals(new DecimalNumber('240')));
        $this->assertTrue($price->getTaxAmount()->equals(new DecimalNumber('40')));
    }

    public function testSetTaxRateRecomputesFromTaxExcluded(): void
    {
        $price = new TaxablePrice(new DecimalNumber('100'), new TaxRate(new DecimalNumber('20')));
        $price->setTaxRate(new TaxRate(new DecimalNumber('10')));

        $this->assertTrue($price->getTaxExcluded()->equals(new DecimalNumber('100')));
        $this->assertTrue($price->getTaxIncluded()->equals(new DecimalNumber('110')));
        $this->assertTrue($price->getTaxAmount()->equals(new DecimalNumber('10')));
    }

    public function testWithZeroTaxRate(): void
    {
        $price = new TaxablePrice(new DecimalNumber('50'), TaxRate::zero());

        $this->assertTrue($price->getTaxExcluded()->equals(new DecimalNumber('50')));
        $this->assertTrue($price->getTaxIncluded()->equals(new DecimalNumber('50')));
        $this->assertTrue($price->getTaxAmount()->equalsZero());
    }

    public function testComputeTaxAmount(): void
    {
        $price = new TaxablePrice(new DecimalNumber('100'), new TaxRate(new DecimalNumber('20')));
        $this->assertTrue($price->computeTaxAmount()->equals(new DecimalNumber('20')));
    }

    public function testComputeTaxAmountWithDecimalRate(): void
    {
        $price = new TaxablePrice(new DecimalNumber('200'), new TaxRate(new DecimalNumber('5.5')));
        $this->assertTrue($price->computeTaxAmount()->equals(new DecimalNumber('11')));
    }

    public function testPlusWithSameTaxRate(): void
    {
        $a = new TaxablePrice(new DecimalNumber('100'), new TaxRate(new DecimalNumber('20')));
        $b = new TaxablePrice(new DecimalNumber('50'), new TaxRate(new DecimalNumber('20')));

        $a->plus($b);

        $this->assertTrue($a->getTaxExcluded()->equals(new DecimalNumber('150')));
        $this->assertTrue($a->getTaxIncluded()->equals(new DecimalNumber('180')));
    }

    public function testPlusWithDifferentTaxRateThrows(): void
    {
        $a = new TaxablePrice(new DecimalNumber('100'), new TaxRate(new DecimalNumber('20')));
        $b = new TaxablePrice(new DecimalNumber('50'), new TaxRate(new DecimalNumber('10')));

        $this->expectException(TaxRateMismatchException::class);
        $a->plus($b);
    }

    public function testMinusWithSameTaxRate(): void
    {
        $a = new TaxablePrice(new DecimalNumber('100'), new TaxRate(new DecimalNumber('20')));
        $b = new TaxablePrice(new DecimalNumber('30'), new TaxRate(new DecimalNumber('20')));

        $a->minus($b);

        $this->assertTrue($a->getTaxExcluded()->equals(new DecimalNumber('70')));
        $this->assertTrue($a->getTaxIncluded()->equals(new DecimalNumber('84')));
    }

    public function testMinusWithDifferentTaxRateThrows(): void
    {
        $a = new TaxablePrice(new DecimalNumber('100'), new TaxRate(new DecimalNumber('20')));
        $b = new TaxablePrice(new DecimalNumber('30'), new TaxRate(new DecimalNumber('5')));

        $this->expectException(TaxRateMismatchException::class);
        $a->minus($b);
    }

    public function testTimesWithScalar(): void
    {
        $a = new TaxablePrice(new DecimalNumber('10'), new TaxRate(new DecimalNumber('20')));

        $a->times(new DecimalNumber('3'));

        $this->assertTrue($a->getTaxExcluded()->equals(new DecimalNumber('30')));
        $this->assertTrue($a->getTaxIncluded()->equals(new DecimalNumber('36')));
    }

    public function testDividedByWithScalar(): void
    {
        $a = new TaxablePrice(new DecimalNumber('100'), new TaxRate(new DecimalNumber('20')));

        $a->dividedBy(new DecimalNumber('4'));

        $this->assertTrue($a->getTaxExcluded()->equals(new DecimalNumber('25')));
        $this->assertTrue($a->getTaxIncluded()->equals(new DecimalNumber('30')));
    }
}
