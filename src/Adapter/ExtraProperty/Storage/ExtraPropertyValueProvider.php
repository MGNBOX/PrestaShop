<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\ExtraProperty\Storage;

use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyScope;
use PrestaShop\PrestaShop\Core\ExtraProperty\Registry\EntityExtraFieldRegistryInterface;
use PrestaShop\PrestaShop\Core\ExtraProperty\Storage\ExtraPropertyReaderInterface;
use PrestaShop\PrestaShop\Core\ExtraProperty\Storage\ExtraPropertyValueProviderInterface;

class ExtraPropertyValueProvider implements ExtraPropertyValueProviderInterface
{
    public function __construct(
        protected readonly EntityExtraFieldRegistryInterface $registry,
        protected readonly ExtraPropertyReaderInterface $reader,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function findCustomFieldDefinition(string $entityName, string $fieldName, ?string $fieldScope = null): ?array
    {
        if (null !== $fieldScope && !in_array($fieldScope, ExtraPropertyScope::values(), true)) {
            return null;
        }

        $matchingDefinitions = [];
        foreach ($this->registry->getByEntityNameAllScopes($entityName) as $definition) {
            if (($definition['field_name'] ?? null) !== $fieldName) {
                continue;
            }

            $definitionScope = (string) ($definition['field_scope'] ?? '');
            if (!in_array($definitionScope, ExtraPropertyScope::values(), true)) {
                continue;
            }
            if (null !== $fieldScope && $definitionScope !== $fieldScope) {
                continue;
            }

            $matchingDefinitions[] = $definition;
        }

        if (null !== $fieldScope) {
            return $matchingDefinitions[0] ?? null;
        }
        if (count($matchingDefinitions) !== 1) {
            return null;
        }

        return $matchingDefinitions[0];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraProperties(
        string $entityName,
        string $primaryKeyName,
        int $entityId,
        ?int $langId = null,
        ?int $shopId = null,
        bool $isLangMultishop = false,
        bool $displayFrontOnly = false
    ): array {
        return $this->reader->getExtraProperties(
            $entityName,
            $primaryKeyName,
            $entityId,
            $langId,
            $shopId,
            $isLangMultishop,
            $displayFrontOnly
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFrontExtraProperties(
        string $entityName,
        string $primaryKeyName,
        int $entityId,
        ?int $langId = null,
        ?int $shopId = null
    ): array {
        return $this->reader->getExtraProperties(
            $entityName,
            $primaryKeyName,
            $entityId,
            $langId,
            $shopId,
            true,
            true
        );
    }
}
