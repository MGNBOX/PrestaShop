<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\QueryResult\ExtraPropertyDefinitionInfo;
use Traversable;

/**
 * Immutable, iterable collection of extra property definitions.
 *
 * Each item is a typed ExtraPropertyDefinitionInfo value object.
 * Provides fluent helpers for filtering and inspection without modifying the original data.
 *
 * @implements IteratorAggregate<int, ExtraPropertyDefinitionInfo>
 */
final class ExtraPropertyDefinitionCollection implements Countable, IteratorAggregate
{
    /** @var list<ExtraPropertyDefinitionInfo> */
    private readonly array $definitions;

    /**
     * @param list<ExtraPropertyDefinitionInfo> $definitions
     */
    public function __construct(array $definitions)
    {
        $this->definitions = array_values($definitions);
    }

    /**
     * Creates a collection from an empty state.
     */
    public static function empty(): self
    {
        return new self([]);
    }

    // -------------------------------------------------------------------------
    // Countable / IteratorAggregate
    // -------------------------------------------------------------------------

    public function count(): int
    {
        return count($this->definitions);
    }

    /**
     * @return Traversable<int, ExtraPropertyDefinitionInfo>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->definitions);
    }

    // -------------------------------------------------------------------------
    // Inspection helpers
    // -------------------------------------------------------------------------

    public function isEmpty(): bool
    {
        return empty($this->definitions);
    }

    /**
     * Returns the first definition, or null when the collection is empty.
     *
     * @return ExtraPropertyDefinitionInfo|null
     */
    public function first(): ?ExtraPropertyDefinitionInfo
    {
        return $this->definitions[0] ?? null;
    }

    /**
     * Returns the raw definitions array.
     *
     * @return list<ExtraPropertyDefinitionInfo>
     */
    public function toArray(): array
    {
        return $this->definitions;
    }

    /**
     * Returns unique module names present in this collection.
     * Core fields (module_name = NULL) are represented by the '_core' key.
     *
     * @return list<string>
     */
    public function getModuleNames(): array
    {
        $seen = [];
        foreach ($this->definitions as $definition) {
            $name = null !== $definition->getModuleName() ? $definition->getModuleName() : '_core';
            $seen[$name] = true;
        }

        return array_keys($seen);
    }

    // -------------------------------------------------------------------------
    // Filtering (return new immutable instances)
    // -------------------------------------------------------------------------

    /**
     * Returns a new collection filtered to the given module.
     *
     * Pass null to get core (no-module) definitions.
     * Pass '_core' as a string alias for core fields.
     */
    public function filterByModule(?string $moduleName): self
    {
        // '_core', null and '' all refer to core fields (module_name IS NULL in DB).
        $isCore = null === $moduleName || '_core' === $moduleName || '' === $moduleName;
        $filtered = array_filter(
            $this->definitions,
            static function (ExtraPropertyDefinitionInfo $d) use ($isCore, $moduleName): bool {
                $defModule = $d->getModuleName();
                if ($isCore) {
                    return null === $defModule;
                }

                return $defModule === $moduleName;
            }
        );

        return new self(array_values($filtered));
    }

    /**
     * Returns a new collection filtered to the given scope.
     *
     * Accepts an ExtraPropertyScope instance or the raw string value ('common', 'lang', 'shop').
     */
    public function filterByScope(ExtraPropertyScope|string $scope): self
    {
        $scopeValue = $scope instanceof ExtraPropertyScope ? $scope->value : $scope;
        $filtered = array_filter(
            $this->definitions,
            static fn (ExtraPropertyDefinitionInfo $d): bool => $d->getFieldScope() === $scopeValue
        );

        return new self(array_values($filtered));
    }

    /**
     * Returns a new collection containing only definitions with display_form = 1.
     */
    public function withDisplayForm(): self
    {
        return new self(array_values(array_filter(
            $this->definitions,
            static fn (ExtraPropertyDefinitionInfo $d): bool => $d->isDisplayForm()
        )));
    }

    /**
     * Returns a new collection containing only definitions with display_grid = 1.
     */
    public function withDisplayGrid(): self
    {
        return new self(array_values(array_filter(
            $this->definitions,
            static fn (ExtraPropertyDefinitionInfo $d): bool => $d->isDisplayGrid()
        )));
    }

    /**
     * Returns a new collection containing only definitions with display_api = 1.
     */
    public function withDisplayApi(): self
    {
        return new self(array_values(array_filter(
            $this->definitions,
            static fn (ExtraPropertyDefinitionInfo $d): bool => $d->isDisplayApi()
        )));
    }
}
