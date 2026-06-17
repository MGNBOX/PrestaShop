<?php

/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Api;

/**
 * Read-side bridge between the Admin API and the extra property system.
 *
 * Implemented in the PrestaShopBundle Admin API layer; consumed from the Core event subscriber through
 * this interface so Core never depends on the bundle implementation.
 */
interface ExtraPropertyApiResponseInjectorInterface
{
    /**
     * Returns the given normalized item enriched with an `extraProperties` sub-object when the operation
     * (URI template + HTTP method) has matching definitions and the item carries a resolvable identifier.
     * Returns the item unchanged otherwise.
     *
     * LANG-scope values are exposed as locale-indexed objects (e.g. {"en-US": ...}); SHOP-scope values are
     * flattened to a scalar in a single-shop context. Field names keep their declared (snake_case) naming.
     *
     * @param array<string, mixed> $item Normalized resource item (single entity, or one element of a list)
     * @param string $resourceClass Fully-qualified ApiResource class (used to resolve the identifier property)
     *
     * @return array<string, mixed>
     */
    public function injectIntoItem(array $item, string $resourceClass, string $uriTemplate, string $method): array;
}
