<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Domain\ExtraProperty\QueryResult;

/**
 * Holds extra property values for a single entity instance, grouped by module name.
 *
 * Structure:
 * [
 *   '_core'    => ['property_name' => value, ...],
 *   'mymodule' => ['property_name' => value, ...],
 * ]
 *
 * For lang-scope fields, each value is itself an array keyed by id_lang (int).
 * For shop-scope fields, each value is an array keyed by id_shop (int).
 * Callers needing locale strings must perform id_lang → locale conversion themselves.
 */
class ExtraPropertyValuesResult
{
    /**
     * @param array<string, array<string, mixed>> $valuesByModule Values grouped by display module key
     */
    public function __construct(
        protected readonly array $valuesByModule,
    ) {
    }

    /**
     * Returns all values grouped by module display key.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getValuesByModule(): array
    {
        return $this->valuesByModule;
    }

    /**
     * Returns the values for one specific module, or an empty array when the module has no values.
     *
     * @param string $moduleName Display module key (e.g. '_core', 'mymodule')
     *
     * @return array<string, mixed>
     */
    public function getModuleValues(string $moduleName): array
    {
        return $this->valuesByModule[$moduleName] ?? [];
    }

    /**
     * Returns true when no extra property values were found for the entity.
     */
    public function isEmpty(): bool
    {
        return empty($this->valuesByModule);
    }
}
