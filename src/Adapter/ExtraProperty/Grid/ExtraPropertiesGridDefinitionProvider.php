<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */
declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\ExtraProperty\Grid;

use PrestaShop\PrestaShop\Core\ExtraProperty\Registry\EntityExtraFieldRegistryInterface;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyNaming;

/**
 * Provides extra field definitions for Back Office Symfony grids.
 *
 * Filters on display_grid=1 and normalizes module_name to '_core'.
 */
class ExtraPropertiesGridDefinitionProvider
{
    public function __construct(
        protected readonly EntityExtraFieldRegistryInterface $registry,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDefinitionsForGrid(string $gridId): array
    {
        $definitions = $this->findDefinitionsWithGridFallbacks($gridId);
        if (empty($definitions)) {
            return [];
        }

        $result = [];
        foreach ($definitions as $definition) {
            if (empty($definition['display_grid'])) {
                continue;
            }

            $definition['module_name'] = !empty($definition['module_name'])
                ? (string) $definition['module_name']
                : ExtraPropertyNaming::CORE_MODULE_KEY;

            $result[] = $definition;
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function findDefinitionsWithGridFallbacks(string $gridId): array
    {
        foreach ($this->buildGridIdCandidates($gridId) as $candidate) {
            $definitions = $this->registry->getByEntityNameAllScopes($candidate);
            if (!empty($definitions)) {
                return $definitions;
            }
        }

        return [];
    }

    /**
     * @return string[]
     */
    protected function buildGridIdCandidates(string $gridId): array
    {
        $name = trim($gridId);
        if ('' === $name) {
            return [];
        }

        $candidates = [$name];

        if (!str_ends_with($name, 's')) {
            $candidates[] = $name . 's';
        } else {
            $candidates[] = rtrim($name, 's');
        }

        return array_values(array_unique(array_filter($candidates, static fn (string $v): bool => '' !== $v)));
    }
}
