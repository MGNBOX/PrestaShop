<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\ExtraProperty\QueryHandler;

use PrestaShop\PrestaShop\Core\CommandBus\Attributes\AsQueryHandler;
use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\Query\GetExtraPropertyDefinitions;
use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\QueryHandler\GetExtraPropertyDefinitionsHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\QueryResult\ExtraPropertyDefinitionInfo;
use PrestaShop\PrestaShop\Core\ExtraProperty\Repository\ExtraPropertyDefinitionRepositoryInterface;

/**
 * Returns extra property definitions from the registry for a given entity.
 *
 * Reads all definitions via the repository (which is cache-decorated in production)
 * and converts them to typed ExtraPropertyDefinitionInfo value objects.
 * An optional module name filter restricts the result to one module's fields.
 */
#[AsQueryHandler]
class GetExtraPropertyDefinitionsHandler implements GetExtraPropertyDefinitionsHandlerInterface
{
    public function __construct(
        protected readonly ExtraPropertyDefinitionRepositoryInterface $repository,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @return ExtraPropertyDefinitionInfo[]
     */
    public function handle(GetExtraPropertyDefinitions $query): array
    {
        $rows = $this->repository->getByEntityNameAllScopes($query->getEntityName());
        if (empty($rows)) {
            return [];
        }

        $moduleName = $query->getModuleName();
        $result = [];

        foreach ($rows as $row) {
            // Apply module filter when requested
            if (null !== $moduleName && ($row['module_name'] ?? '') !== $moduleName) {
                continue;
            }

            $result[] = ExtraPropertyDefinitionInfo::fromRow($row);
        }

        return $result;
    }
}
