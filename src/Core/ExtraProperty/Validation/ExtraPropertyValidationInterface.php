<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Validation;

/**
 * Validation contract used by the ExtraProperty feature.
 *
 * Current implementation delegates to legacy Validate::xxx methods.
 * A future V2 can replace this bridge with Symfony constraints without
 * changing callers.
 */
interface ExtraPropertyValidationInterface
{
    /**
     * Checks if a value is a valid SQL table/identifier token.
     */
    public function isTableOrIdentifier(string $value): bool;

    /**
     * Checks if a value is a valid module technical name.
     */
    public function isModuleName(string $value): bool;

    /**
     * Returns true when a validator method name is available.
     */
    public function hasValidator(string $validator): bool;

    /**
     * Executes one named validator against a value.
     */
    public function validateByName(string $validator, mixed $value): bool;
}
