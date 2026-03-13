<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\ExtraProperty\Registry;

use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyDefinitionCollection;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyScope;
use PrestaShop\PrestaShop\Core\ExtraProperty\Registry\EntityExtraFieldRegistryInterface;
use PrestaShop\PrestaShop\Core\ExtraProperty\Registry\ExtraPropertyRegistryInterface;
use Symfony\Component\Cache\Exception\LogicException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Cache-decorating registry.
 *
 * Wraps ExtraPropertyRegistry and caches the result of getByEntityNameAllScopes() in the
 * Symfony cache.app pool when available; otherwise uses a FilesystemAdapter under the legacy
 * cache directory (same layout as other PrestaShop filesystem caches, e.g. CLDR).
 * All write operations delegate to the inner registry and invalidate the relevant cache
 * entry on both pools when cache.app is present so front-office and back-office stay in sync.
 *
 * Cache key scheme: "extra_property_definition_{entityName}" (one entry per entity).
 * Cache tags: ["extra_property_definition", "extra_property_definition_{entityName}"] (when the pool supports tags).
 */
class CachedExtraPropertyRegistry implements EntityExtraFieldRegistryInterface
{
    private const CACHE_KEY_PREFIX = 'extra_property_definition_';

    public function __construct(
        protected readonly ExtraPropertyRegistryInterface $registry,
        protected readonly ?CacheInterface $cacheApp,
        protected readonly CacheInterface $filesystemDefinitionCache,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinitionCollection(string $entityName): ExtraPropertyDefinitionCollection
    {
        return new ExtraPropertyDefinitionCollection($this->getByEntityNameAllScopes($entityName));
    }

    /**
     * {@inheritdoc}
     */
    public function getByEntityNameAllScopes(string $entityName): array
    {
        $cacheKey = $this->buildCacheKey($entityName);

        return $this->getEffectiveCache()->get($cacheKey, function (ItemInterface $item) use ($entityName): array {
            try {
                $item->tag(['extra_property_definition', 'extra_property_definition_' . $entityName]);
            } catch (LogicException) {
                // Pool may not be tag-aware; key-based invalidation still works.
            }

            return $this->registry->getByEntityNameAllScopes($entityName);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getByEntityName(string $entityName, string $fieldScope = 'common'): array
    {
        $result = array_filter(
            $this->getByEntityNameAllScopes($entityName),
            static fn (array $definition): bool => ($definition['field_scope'] ?? null) === $fieldScope
        );

        return array_values($result);
    }

    /**
     * {@inheritdoc}
     */
    public function getByEntityAndFieldName(string $entityName, string $fieldName, string $fieldScope = 'common'): ?array
    {
        foreach ($this->getByEntityNameAllScopes($entityName) as $definition) {
            if (
                ($definition['field_name'] ?? null) === $fieldName
                && ($definition['field_scope'] ?? null) === $fieldScope
            ) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasExtraProperties(string $entityName): bool
    {
        return !empty($this->getByEntityNameAllScopes($entityName));
    }

    /**
     * {@inheritdoc}
     */
    public function register(string $entityName, string $fieldName, ?string $defaultModuleName = null, array $options = []): bool
    {
        $result = $this->registry->register($entityName, $fieldName, $defaultModuleName, $options);
        if ($result) {
            $this->invalidateEntityCache($entityName);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function unregister(string $entityName, string $fieldName, ?string $moduleName, ExtraPropertyScope|string $fieldScope = 'common', bool $dropColumn = false): bool
    {
        $result = $this->registry->unregister($entityName, $fieldName, $moduleName, $fieldScope, $dropColumn);
        if ($result) {
            $this->invalidateEntityCache($entityName);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function unregisterById(int $idExtraPropertyDefinition, bool $dropColumn = false): bool
    {
        // Load the definition first to know which entity cache to invalidate.
        $definition = $this->registry->getDefinitionById($idExtraPropertyDefinition);
        $result = $this->registry->unregisterById($idExtraPropertyDefinition, $dropColumn);
        if ($result && null !== $definition) {
            $this->invalidateEntityCache((string) ($definition['entity_name'] ?? ''));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinitionById(int $idExtraPropertyDefinition): ?array
    {
        return $this->registry->getDefinitionById($idExtraPropertyDefinition);
    }

    protected function buildCacheKey(string $entityName): string
    {
        return self::CACHE_KEY_PREFIX . preg_replace('/[^a-zA-Z0-9_]/', '_', $entityName);
    }

    /**
     * Pool used for reads: Symfony app pool when available, otherwise filesystem cache (FO legacy container).
     */
    protected function getEffectiveCache(): CacheInterface
    {
        return $this->cacheApp ?? $this->filesystemDefinitionCache;
    }

    /**
     * Removes the entity key from the filesystem pool and from cache.app when present so BO and FO stay aligned.
     */
    protected function invalidateEntityCache(string $entityName): void
    {
        if ('' === $entityName) {
            return;
        }

        $cacheKey = $this->buildCacheKey($entityName);
        $this->filesystemDefinitionCache->delete($cacheKey);

        if (null !== $this->cacheApp) {
            $this->cacheApp->delete($cacheKey);
        }
    }
}
