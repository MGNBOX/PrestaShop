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
use Traversable;

/**
 * Immutable, iterable collection of extra property definition rows.
 *
 * Each item is an associative array as returned by ExtraPropertyDefinitionRepositoryInterface.
 * Provides fluent helpers for filtering and inspection without modifying the original data.
 *
 * @implements IteratorAggregate<int, array<string, mixed>>
 */
final class ExtraPropertyDefinitionCollection implements Countable, IteratorAggregate
{
    /** @var list<array<string, mixed>> */
    private readonly array $definitions;

    /**
     * @param array<int, array<string, mixed>> $definitions raw rows from the repository
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
     * @return Traversable<int, array<string, mixed>>
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
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        return $this->definitions[0] ?? null;
    }

    /**
     * Returns the raw definitions array.
     *
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return $this->definitions;
    }

    /**
     * Returns unique module names present in this collection.
     * Core fields (module_name = NULL or '') are represented by the '_core' key.
     *
     * @return list<string>
     */
    public function getModuleNames(): array
    {
        $seen = [];
        foreach ($this->definitions as $definition) {
            $name = !empty($definition['module_name']) ? (string) $definition['module_name'] : '_core';
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
        // '_core', null and '' all refer to core fields (module_name = '' in DB).
        $normalizedModule = (null === $moduleName || '_core' === $moduleName || '' === $moduleName) ? '' : $moduleName;
        $filtered = array_filter(
            $this->definitions,
            static fn (array $d): bool => (string) ($d['module_name'] ?? '') === $normalizedModule
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
            static fn (array $d): bool => ($d['field_scope'] ?? null) === $scopeValue
        );

        return new self(array_values($filtered));
    }

    /**
     * Returns a new collection containing only definitions with display_front = 1.
     */
    public function withDisplayFront(): self
    {
        return new self(array_values(array_filter(
            $this->definitions,
            static fn (array $d): bool => !empty($d['display_front'])
        )));
    }

    /**
     * Returns a new collection containing only definitions with display_bo = 1.
     */
    public function withDisplayBo(): self
    {
        return new self(array_values(array_filter(
            $this->definitions,
            static fn (array $d): bool => !empty($d['display_bo'])
        )));
    }

    /**
     * Returns a new collection containing only definitions with display_grid = 1.
     */
    public function withDisplayGrid(): self
    {
        return new self(array_values(array_filter(
            $this->definitions,
            static fn (array $d): bool => !empty($d['display_grid'])
        )));
    }

    /**
     * Returns a new collection containing only definitions with display_api = 1.
     */
    public function withDisplayApi(): self
    {
        return new self(array_values(array_filter(
            $this->definitions,
            static fn (array $d): bool => !empty($d['display_api'])
        )));
    }
}
