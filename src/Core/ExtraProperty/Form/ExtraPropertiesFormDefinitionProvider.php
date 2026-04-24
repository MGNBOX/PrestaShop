<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Form;

use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyDefinitionCollection;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyNaming;
use PrestaShop\PrestaShop\Core\ExtraProperty\Repository\ExtraPropertyDefinitionRepositoryInterface;

/**
 * Provides extra field definitions for Back Office Symfony forms.
 *
 * Filters on display_form=1 and returns a typed ExtraPropertyDefinitionCollection.
 * Entity name resolution (plural/singular, suffix stripping) is handled by
 * ExtraPropertyNaming::resolveEntityTableCandidates().
 */
class ExtraPropertiesFormDefinitionProvider
{
    public function __construct(
        protected readonly ExtraPropertyDefinitionRepositoryInterface $repository,
    ) {
    }

    public function getDefinitionsForEntity(string $entityName): ExtraPropertyDefinitionCollection
    {
        foreach (ExtraPropertyNaming::resolveEntityTableCandidates($entityName) as $candidate) {
            $collection = $this->repository->getDefinitionCollection($candidate);
            if (!$collection->isEmpty()) {
                return $collection->withDisplayForm();
            }
        }

        return ExtraPropertyDefinitionCollection::empty();
    }
}
