<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Domain\ExtraProperty\QueryHandler;

use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\Query\GetExtraPropertyDefinitions;
use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\QueryResult\ExtraPropertyDefinitionInfo;

/**
 * Contract for the handler that retrieves extra property definitions.
 */
interface GetExtraPropertyDefinitionsHandlerInterface
{
    /**
     * Returns an array of definition info objects for the given entity and optional module filter.
     *
     * @param GetExtraPropertyDefinitions $query
     *
     * @return ExtraPropertyDefinitionInfo[]
     */
    public function handle(GetExtraPropertyDefinitions $query): array;
}
