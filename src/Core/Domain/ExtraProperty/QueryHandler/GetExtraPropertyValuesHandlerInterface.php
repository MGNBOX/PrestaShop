<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Domain\ExtraProperty\QueryHandler;

use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\Query\GetExtraPropertyValues;
use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\QueryResult\ExtraPropertyValuesResult;

/**
 * Contract for the handler that reads extra property values for a single entity instance.
 */
interface GetExtraPropertyValuesHandlerInterface
{
    /**
     * Returns all extra property values for the entity described by the query.
     *
     * Lang-scope values are indexed by id_lang (int).
     * Shop-scope values are indexed by id_shop (int).
     *
     * @param GetExtraPropertyValues $query
     *
     * @return ExtraPropertyValuesResult
     */
    public function handle(GetExtraPropertyValues $query): ExtraPropertyValuesResult;
}
