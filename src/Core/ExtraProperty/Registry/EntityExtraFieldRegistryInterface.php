<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Registry;

use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyScope;

/**
 * Combined interface for backward compatibility.
 *
 * Extends ExtraPropertyRegistryInterface (register/unregister) which itself extends
 * ExtraPropertyDefinitionRepositoryInterface (read operations).
 *
 * New code should prefer the more specific interfaces:
 *
 * @see ExtraPropertyRegistryInterface   for register/unregister operations
 * @see ExtraPropertyDefinitionRepositoryInterface   for read-only definition access
 */
interface EntityExtraFieldRegistryInterface extends ExtraPropertyRegistryInterface
{
    /**
     * @param string $entityName
     * @param string $fieldName
     * @param string|null $defaultModuleName
     * @param array<string, mixed> $options see ExtraPropertyOptions for all supported keys
     *
     * @return bool
     */
    public function register(string $entityName, string $fieldName, ?string $defaultModuleName, array $options = []): bool;

    /**
     * @param string $entityName
     * @param string $fieldName
     * @param string|null $moduleName
     * @param ExtraPropertyScope|string $fieldScope
     * @param bool $dropColumn
     *
     * @return bool
     */
    public function unregister(string $entityName, string $fieldName, ?string $moduleName, ExtraPropertyScope|string $fieldScope = 'common', bool $dropColumn = false): bool;

    /**
     * @param int $idExtraPropertyDefinition
     * @param bool $dropColumn
     *
     * @return bool
     */
    public function unregisterById(int $idExtraPropertyDefinition, bool $dropColumn = false): bool;
}
