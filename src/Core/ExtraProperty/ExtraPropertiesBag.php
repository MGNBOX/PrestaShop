<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty;

use ArrayAccess;
use ArrayIterator;
use Closure;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Lazy-loading value bag for extra properties on an ObjectModel instance.
 *
 * Keys are storage column names: ExtraPropertyNaming::storageColumnName($module, $field).
 * Values are loaded from the DB on first access; writes are tracked for persistence.
 *
 * Usage:
 *   $product->extra_properties['demoextrafield_date_last_seen']         // read
 *   $product->extra_properties['demoextrafield_date_last_seen'] = $val  // write + mark dirty
 *   foreach ($product->extra_properties as $col => $val) { ... }       // iterate (triggers load)
 *   json_encode($product->extra_properties)                             // serialize
 */
final class ExtraPropertiesBag implements ArrayAccess, IteratorAggregate, JsonSerializable
{
    private bool $loaded = false;
    /** @var array<string, mixed> */
    private array $values = [];
    /** @var array<string, mixed> */
    private array $modifiedValues = [];

    public function __construct(private readonly Closure $loader)
    {
    }

    private function ensureLoaded(): void
    {
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;
        $this->values = ($this->loader)();
    }

    public function offsetExists(mixed $offset): bool
    {
        $this->ensureLoaded();

        return array_key_exists($offset, $this->values);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $this->ensureLoaded();

        return $this->values[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->ensureLoaded();
        $this->values[$offset] = $value;
        $this->modifiedValues[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->ensureLoaded();
        unset($this->values[$offset]);
        $this->modifiedValues[$offset] = null;
    }

    public function getIterator(): Traversable
    {
        $this->ensureLoaded();

        return new ArrayIterator($this->values);
    }

    public function jsonSerialize(): mixed
    {
        $this->ensureLoaded();

        return $this->values;
    }

    public function hasModifications(): bool
    {
        return !empty($this->modifiedValues);
    }

    /** @return array<string, mixed> Flat column_name => value map of all modified entries. */
    public function getModifiedValues(): array
    {
        return $this->modifiedValues;
    }

    /** @return array<string, mixed> All loaded values (flat). Triggers lazy load. */
    public function toArray(): array
    {
        $this->ensureLoaded();

        return $this->values;
    }
}
