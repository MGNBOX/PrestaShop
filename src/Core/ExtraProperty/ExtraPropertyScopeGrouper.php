<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty;

/**
 * Groups extra property definitions by scope using enum values as source of truth.
 */
final class ExtraPropertyScopeGrouper
{
    /**
     * @param array<int, array<string, mixed>> $definitions
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function groupDefinitionsByScope(array $definitions): array
    {
        $definitionsByScope = [];
        foreach (ExtraPropertyScope::values() as $scope) {
            $definitionsByScope[$scope] = [];
        }

        foreach ($definitions as $definition) {
            $fieldScope = (string) ($definition['field_scope'] ?? '');
            if (!array_key_exists($fieldScope, $definitionsByScope)) {
                continue;
            }
            $definitionsByScope[$fieldScope][] = $definition;
        }

        return $definitionsByScope;
    }
}
