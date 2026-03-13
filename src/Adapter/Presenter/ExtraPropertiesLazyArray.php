<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\Presenter;

use Context;
use ObjectModel;
use PrestaShop\PrestaShop\Core\ExtraProperty\Storage\ExtraPropertyValueProviderInterface;

/**
 * Resolves front-office extra field values (grouped by module) for presentation.
 *
 * This is not an AbstractLazyArray: it only encapsulates the call to
 * ExtraPropertyValueProviderInterface::getFrontExtraProperties() using entity metadata
 * from ObjectModel definitions (table + primary key) and the current context (lang / shop).
 */
final class ExtraPropertiesLazyArray
{
    /**
     * @param ExtraPropertyValueProviderInterface|null $provider
     * @param string $entityTable Registry entity name (ObjectModel definition `table`)
     * @param string $primaryKeyName Primary column name (ObjectModel definition `primary`)
     * @param int $entityId Entity row id
     * @param int $langId Language id for lang-scoped fields
     * @param int $shopId Shop id for shop / lang multishop resolution
     */
    public function __construct(
        private readonly ?ExtraPropertyValueProviderInterface $provider,
        private readonly string $entityTable,
        private readonly string $primaryKeyName,
        private readonly int $entityId,
        private readonly int $langId,
        private readonly int $shopId,
    ) {
    }

    /**
     * Builds a resolver from a loaded ObjectModel instance (uses $object->def and $object->id).
     *
     * @param ObjectModel $object
     * @param ExtraPropertyValueProviderInterface|null $provider
     * @param Context $context
     */
    public static function fromObjectModel(
        ObjectModel $object,
        ?ExtraPropertyValueProviderInterface $provider,
        Context $context
    ): self {
        /** @var array<string, mixed> $def */
        $def = ObjectModel::getDefinition($object);

        return new self(
            $provider,
            (string) ($def['table'] ?? ''),
            (string) ($def['primary'] ?? ''),
            (int) $object->id,
            (int) $context->language->id,
            (int) $context->shop->id
        );
    }

    /**
     * Builds a resolver from an ObjectModel class name and a row id (e.g. product array in presenters).
     *
     * @param class-string<ObjectModel> $objectModelClass
     * @param int $entityId
     * @param ExtraPropertyValueProviderInterface|null $provider
     * @param Context $context
     */
    public static function fromObjectModelClass(
        string $objectModelClass,
        int $entityId,
        ?ExtraPropertyValueProviderInterface $provider,
        Context $context
    ): self {
        if (!class_exists($objectModelClass) || !is_subclass_of($objectModelClass, ObjectModel::class)) {
            return new self(null, '', '', 0, 0, 0);
        }

        $def = ObjectModel::getDefinition($objectModelClass);
        if (!is_array($def) || empty($def['table']) || empty($def['primary'])) {
            return new self(null, '', '', 0, 0, 0);
        }

        return new self(
            $provider,
            (string) $def['table'],
            (string) $def['primary'],
            $entityId,
            (int) $context->language->id,
            (int) $context->shop->id
        );
    }

    /**
     * Returns extra fields grouped by module for front-office display (display_front = 1).
     *
     * @return array<string, array<string, mixed>>
     */
    public function getValues(): array
    {
        if (null === $this->provider || $this->entityId <= 0 || '' === $this->entityTable || '' === $this->primaryKeyName) {
            return [];
        }

        return $this->provider->getFrontExtraProperties(
            $this->entityTable,
            $this->primaryKeyName,
            $this->entityId,
            $this->langId,
            $this->shopId
        );
    }
}
