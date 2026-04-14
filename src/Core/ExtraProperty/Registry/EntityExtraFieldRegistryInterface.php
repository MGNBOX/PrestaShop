<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Registry;

use PrestaShop\PrestaShop\Core\ExtraProperty\Repository\ExtraPropertyDefinitionRepositoryInterface;

/**
 * Combined read+write interface kept for backward compatibility.
 *
 * @deprecated Inject ExtraPropertyDefinitionRepositoryInterface for reads
 *             and ExtraPropertyRegistryInterface for writes instead.
 */
interface EntityExtraFieldRegistryInterface extends ExtraPropertyDefinitionRepositoryInterface, ExtraPropertyRegistryInterface
{
}
