<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Pricing\Exception;

/**
 * Thrown when attempting to combine TaxablePrice instances with different tax rates.
 */
class TaxRateMismatchException extends PricingException
{
}
