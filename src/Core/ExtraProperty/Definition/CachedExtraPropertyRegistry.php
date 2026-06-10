<?php

/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Definition;

/**
 * Thin pass-through decorator for ExtraPropertyRegistryInterface.
 *
 * Cache invalidation after writes is handled by CachedExtraPropertyDefinitionRepository,
 * which implements both ExtraPropertyDefinitionRepositoryInterface and ExtraPropertyDefinitionWriterInterface.
 */
class CachedExtraPropertyRegistry implements ExtraPropertyRegistryInterface
{
    public function __construct(
        protected readonly ExtraPropertyRegistry $inner,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function register(ExtraPropertyDefinition $definition): bool
    {
        return $this->inner->register($definition);
    }

    /**
     * {@inheritdoc}
     */
    public function unregister(ExtraPropertyDefinition $definition, bool $dropColumn = false): bool
    {
        return $this->inner->unregister($definition, $dropColumn);
    }
}
