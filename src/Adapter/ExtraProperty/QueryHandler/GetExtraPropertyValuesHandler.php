<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\ExtraProperty\QueryHandler;

use PrestaShop\PrestaShop\Core\CommandBus\Attributes\AsQueryHandler;
use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\Query\GetExtraPropertyValues;
use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\QueryHandler\GetExtraPropertyValuesHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\QueryResult\ExtraPropertyDefinitionInfo;
use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\QueryResult\ExtraPropertyValuesResult;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyDefinitionCollection;
use PrestaShop\PrestaShop\Core\ExtraProperty\Repository\ExtraPropertyDefinitionRepositoryInterface;
use PrestaShop\PrestaShop\Core\ExtraProperty\Storage\ExtraPropertyReaderInterface;

/**
 * Reads extra property values for a single entity instance across all three scopes.
 *
 * Delegates to ExtraPropertyReader with null lang / null shop to fetch all languages
 * and all shops in one pass — the pattern required by the Admin API.
 *
 * Lang-scope values are returned indexed by id_lang (int).
 * Shop-scope values are returned indexed by id_shop (int).
 * Callers needing locale strings must convert id_lang → locale themselves.
 *
 * When $query->isDisplayApiOnly() is true, definitions without display_api = 1
 * are skipped so that non-exposed fields are never read from the database.
 */
#[AsQueryHandler]
class GetExtraPropertyValuesHandler implements GetExtraPropertyValuesHandlerInterface
{
    public function __construct(
        protected readonly ExtraPropertyDefinitionRepositoryInterface $repository,
        protected readonly ExtraPropertyReaderInterface $reader,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function handle(GetExtraPropertyValues $query): ExtraPropertyValuesResult
    {
        if ($query->getEntityId() <= 0) {
            return new ExtraPropertyValuesResult([]);
        }

        $allDefinitions = $this->repository->getByEntityNameAllScopes($query->getEntityName());
        if (empty($allDefinitions)) {
            return new ExtraPropertyValuesResult([]);
        }

        if ($query->isDisplayApiOnly()) {
            $allDefinitions = array_values(array_filter(
                $allDefinitions,
                static fn (ExtraPropertyDefinitionInfo $def): bool => $def->isDisplayApi()
            ));
        }

        if (empty($allDefinitions)) {
            return new ExtraPropertyValuesResult([]);
        }

        $values = $this->reader->getExtraProperties(
            $query->getEntityName(),
            $query->getPrimaryKeyName(),
            $query->getEntityId(),
            null,  // all languages
            null,  // all shops
            false, // no shop filter on lang table: return all lang rows
            new ExtraPropertyDefinitionCollection($allDefinitions)
        );

        return new ExtraPropertyValuesResult($values);
    }
}
