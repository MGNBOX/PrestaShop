<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Registry;

use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyScope;
use PrestaShop\PrestaShop\Core\ExtraProperty\Repository\ExtraPropertyDefinitionRepositoryInterface;

/**
 * Write interface for extra property definitions: register and unregister operations.
 *
 * Implementations are responsible for persisting the definition row AND ensuring the
 * corresponding SQL column exists in the entity's *_extra table.
 */
interface ExtraPropertyRegistryInterface extends ExtraPropertyDefinitionRepositoryInterface
{
    /**
     * Register or update an extra property definition.
     *
     * When the physical SQL column does not yet exist, it is created.
     * On conflict (same entity+module+field+scope), the definition row is updated.
     *
     * @param string $entityName
     * @param string $fieldName
     * @param string|null $defaultModuleName module owning this field (defaults to null = core field)
     * @param array<string, mixed> $options see ExtraPropertyOptions or EntityExtraFieldRegistryInterface docblock
     *
     * @return bool
     */
    public function register(string $entityName, string $fieldName, ?string $defaultModuleName, array $options = []): bool;

    /**
     * Unregister an extra property definition by entity, field name and scope.
     * When $dropColumn is true, the physical SQL column is also dropped.
     *
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
     * Unregister an extra property definition by its primary key.
     * When $dropColumn is true, the physical SQL column is also dropped.
     *
     * @param int $idExtraPropertyDefinition
     * @param bool $dropColumn
     *
     * @return bool
     */
    public function unregisterById(int $idExtraPropertyDefinition, bool $dropColumn = false): bool;

    /**
     * Loads one definition row directly by primary key.
     *
     * @param int $idExtraPropertyDefinition
     *
     * @return array<string, mixed>|null
     */
    public function getDefinitionById(int $idExtraPropertyDefinition): ?array;
}
