<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\ExtraProperty\BackOffice;

use PrestaShop\PrestaShop\Core\ExtraProperty\Registry\EntityExtraFieldRegistryInterface;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyNaming;

/**
 * Provides extra field definitions for Back Office Symfony forms.
 *
 * Filters on display_bo=1 and normalizes module_name to '_core'.
 */
class ExtraPropertiesFormDefinitionProvider
{
    public function __construct(
        protected readonly EntityExtraFieldRegistryInterface $registry,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDefinitionsForEntity(string $entityName): array
    {
        $definitions = $this->findDefinitionsWithEntityFallbacks($entityName);
        if (empty($definitions)) {
            return [];
        }

        $result = [];
        foreach ($definitions as $definition) {
            if (empty($definition['display_bo'])) {
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
    protected function findDefinitionsWithEntityFallbacks(string $entityName): array
    {
        foreach ($this->buildEntityNameCandidates($entityName) as $candidate) {
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
    protected function buildEntityNameCandidates(string $entityName): array
    {
        $name = trim($entityName);
        if ('' === $name) {
            return [];
        }

        $candidates = [$name];

        $lastUnderscore = strrpos($name, '_');
        if (false !== $lastUnderscore && $lastUnderscore < strlen($name) - 1) {
            $suffix = substr($name, $lastUnderscore + 1);
            $candidates[] = $suffix;
            if (!str_ends_with($suffix, 's')) {
                $candidates[] = $suffix . 's';
            }
        }

        if (!str_ends_with($name, 's')) {
            $candidates[] = $name . 's';
        } else {
            $candidates[] = rtrim($name, 's');
        }

        return array_values(array_unique(array_filter($candidates, static fn (string $v): bool => '' !== $v)));
    }
}
