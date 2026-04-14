<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\ExtraProperty\Registry;

use PrestaShop\PrestaShop\Adapter\ExtraProperty\Repository\CachedExtraPropertyDefinitionRepository;

/**
 * @deprecated Use CachedExtraPropertyDefinitionRepository for reads
 *             and ExtraPropertyRegistry for writes. This file can be deleted.
 */
class CachedExtraPropertyRegistry extends CachedExtraPropertyDefinitionRepository
{
}
