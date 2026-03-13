<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Domain\ExtraProperty\Query;

/**
 * Query to retrieve extra property definitions from the registry.
 *
 * Returns all definitions for a given entity, optionally filtered by module name.
 * Used by the Admin API to list the extra properties exposed for a resource.
 */
class GetExtraPropertyDefinitions
{
    /**
     * @param string $entityName Entity table name (e.g. 'product')
     * @param string|null $moduleName When provided, only definitions belonging to this module are returned
     */
    public function __construct(
        protected readonly string $entityName,
        protected readonly ?string $moduleName = null,
    ) {
    }

    /**
     * Returns the entity table name to query definitions for.
     */
    public function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * Returns the module name filter, or null to return definitions for all modules.
     */
    public function getModuleName(): ?string
    {
        return $this->moduleName;
    }
}
