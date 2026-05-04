<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\Country\AddressFormat;

use AddressFormat;
use PrestaShop\PrestaShop\Core\Domain\Country\AddressFormat\AddressFormatCheckerInterface;

/**
 * Adapter that bridges AddressFormatCheckerInterface to the legacy AddressFormat ObjectModel.
 * Keeps the Domain and Bundle layers free of direct legacy references.
 */
final class LegacyAddressFormatChecker implements AddressFormatCheckerInterface
{
    public function validate(string $format): array
    {
        $tmp = new AddressFormat();
        $tmp->format = $format;

        if ($tmp->checkFormatFields()) {
            return [];
        }

        return $tmp->getErrorList();
    }
}
