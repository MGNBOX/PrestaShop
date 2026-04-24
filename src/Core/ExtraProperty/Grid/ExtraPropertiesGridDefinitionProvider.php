<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Grid;

use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyDefinitionCollection;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyNaming;
use PrestaShop\PrestaShop\Core\ExtraProperty\Repository\ExtraPropertyDefinitionRepositoryInterface;

/**
 * Provides extra field definitions for Back Office Symfony grids.
 *
 * Filters on display_grid=1 and returns a typed ExtraPropertyDefinitionCollection.
 * Grid ID resolution (plural/singular, suffix stripping) is handled by
 * ExtraPropertyNaming::resolveEntityTableCandidates().
 */
class ExtraPropertiesGridDefinitionProvider
{
    public function __construct(
        protected readonly ExtraPropertyDefinitionRepositoryInterface $repository,
    ) {
    }

    public function getDefinitionsForGrid(string $gridId): ExtraPropertyDefinitionCollection
    {
        foreach (ExtraPropertyNaming::resolveEntityTableCandidates($gridId) as $candidate) {
            $collection = $this->repository->getDefinitionCollection($candidate);
            if (!$collection->isEmpty()) {
                return $collection->withDisplayGrid();
            }
        }

        return ExtraPropertyDefinitionCollection::empty();
    }
}
