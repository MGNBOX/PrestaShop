<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Domain\Carrier\ValueObject;

use InvalidArgumentException;
use PrestaShop\Decimal\DecimalNumber;

final class ShippingCostResult
{
    private DecimalNumber $taxExcluded;
    private DecimalNumber $taxIncluded;
    private int $selectedCarrierId;
    private int $precision;

    public function __construct(
        DecimalNumber $taxExcluded,
        DecimalNumber $taxIncluded,
        int $selectedCarrierId,
        int $precision
    ) {
        if ($taxExcluded->isNegative()) {
            throw new InvalidArgumentException('Tax excluded amount cannot be negative');
        }
        if ($taxIncluded->isNegative()) {
            throw new InvalidArgumentException('Tax included amount cannot be negative');
        }
        if ($selectedCarrierId <= 0) {
            throw new InvalidArgumentException('Selected carrier ID must be positive');
        }

        $this->taxExcluded = $taxExcluded;
        $this->taxIncluded = $taxIncluded;
        $this->selectedCarrierId = $selectedCarrierId;
        $this->precision = $precision;
    }

    public function getTaxExcluded(): DecimalNumber
    {
        return $this->taxExcluded;
    }

    public function getTaxIncluded(): DecimalNumber
    {
        return $this->taxIncluded;
    }

    public function getSelectedCarrierId(): int
    {
        return $this->selectedCarrierId;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }
}
