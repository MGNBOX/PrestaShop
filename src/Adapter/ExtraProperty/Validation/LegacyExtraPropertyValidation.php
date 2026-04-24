<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\ExtraProperty\Validation;

use PrestaShop\PrestaShop\Core\ExtraProperty\Validation\ExtraPropertyValidationInterface;
use Validate;

/**
 * Legacy bridge for ExtraProperty validation.
 *
 * This adapter isolates direct calls to Validate::xxx so Core and Bundle
 * layers can stay independent from legacy static helpers.
 */
class LegacyExtraPropertyValidation implements ExtraPropertyValidationInterface
{
    public function isTableOrIdentifier(string $value): bool
    {
        return (bool) Validate::isTableOrIdentifier($value);
    }

    public function isModuleName(string $value): bool
    {
        return (bool) Validate::isModuleName($value);
    }

    public function hasValidator(string $validator): bool
    {
        return '' !== $validator && method_exists(Validate::class, $validator);
    }

    public function validateByName(string $validator, mixed $value): bool
    {
        if (!$this->hasValidator($validator)) {
            return false;
        }

        return (bool) call_user_func([Validate::class, $validator], $value);
    }
}
