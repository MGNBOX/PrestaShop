<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\Presenter;

use Context;
use ObjectModel;
use PrestaShop\PrestaShop\Adapter\ContainerFinder;
use PrestaShop\PrestaShop\Core\ExtraProperty\Storage\ExtraPropertyValueProviderInterface;

/**
 * Resolves extra field values (grouped by module) for an entity instance.
 *
 * This is not an AbstractLazyArray: it only encapsulates the call to
 * ExtraPropertyValueProviderInterface::getExtraProperties() using entity metadata
 * from ObjectModel definitions (table + primary key) and the current context (lang / shop).
 *
 * The static factories resolve the ExtraPropertyValueProviderInterface and the current
 * Context internally so callers only need to supply the entity identity.
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
     * @param bool $isLangMultishop Whether lang-scoped fields should also be filtered by shop (FO multishop pattern)
     */
    public function __construct(
        private readonly ?ExtraPropertyValueProviderInterface $provider,
        private readonly string $entityTable,
        private readonly string $primaryKeyName,
        private readonly int $entityId,
        private readonly int $langId,
        private readonly int $shopId,
        private readonly bool $isLangMultishop = false,
    ) {
    }

    /**
     * Builds a resolver from a loaded ObjectModel instance (uses $object->def and $object->id).
     *
     * @param ObjectModel $object
     */
    public static function fromObjectModel(ObjectModel $object): self
    {
        $provider = null;
        try {
            $containerFinder = new ContainerFinder(Context::getContext());
            /** @var ExtraPropertyValueProviderInterface $provider */
            $provider = $containerFinder->getContainer()->get(ExtraPropertyValueProviderInterface::class);
        } catch (\Throwable) {
        }

        /** @var array<string, mixed> $def */
        $def = ObjectModel::getDefinition($object);
        $context = Context::getContext();

        return new self(
            $provider,
            (string) ($def['table'] ?? ''),
            (string) ($def['primary'] ?? ''),
            (int) $object->id,
            (int) $context->language->id,
            (int) $context->shop->id,
            (bool) $object->isLangMultishop(),
        );
    }

    /**
     * Builds a resolver from an ObjectModel class name and a row id (e.g. product array in presenters).
     *
     * @param class-string<ObjectModel> $objectModelClass
     * @param int $entityId
     */
    public static function fromObjectModelClass(string $objectModelClass, int $entityId): self
    {
        if (!class_exists($objectModelClass) || !is_subclass_of($objectModelClass, ObjectModel::class)) {
            return new self(null, '', '', 0, 0, 0);
        }

        $def = ObjectModel::getDefinition($objectModelClass);
        if (!is_array($def) || empty($def['table']) || empty($def['primary'])) {
            return new self(null, '', '', 0, 0, 0);
        }

        $provider = null;
        try {
            $containerFinder = new ContainerFinder(Context::getContext());
            /** @var ExtraPropertyValueProviderInterface $provider */
            $provider = $containerFinder->getContainer()->get(ExtraPropertyValueProviderInterface::class);
        } catch (\Throwable) {
        }

        $context = Context::getContext();
        $isLangMultishop = !empty($def['multilang']) && !empty($def['multilang_shop']);

        return new self(
            $provider,
            (string) $def['table'],
            (string) $def['primary'],
            $entityId,
            (int) $context->language->id,
            (int) $context->shop->id,
            $isLangMultishop,
        );
    }

    /**
     * Returns extra fields grouped by module for front-office display.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getValues(): array
    {
        if (null === $this->provider || $this->entityId <= 0 || '' === $this->entityTable || '' === $this->primaryKeyName) {
            return [];
        }

        return $this->provider->getExtraProperties(
            $this->entityTable,
            $this->primaryKeyName,
            $this->entityId,
            $this->langId,
            $this->shopId,
            $this->isLangMultishop
        );
    }
}
