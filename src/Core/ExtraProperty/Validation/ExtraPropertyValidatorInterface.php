<?php

/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Validation;

use PrestaShop\PrestaShop\Core\ExtraProperty\Definition\ExtraPropertyDefinition;
use PrestaShop\PrestaShop\Core\ExtraProperty\Definition\ExtraPropertyDefinitionCollection;

/**
 * Validation contract for the ExtraProperty feature.
 *
 * Value validation (validateValue / validate) is used by forms, API, and ObjectModel.
 * The structural static helpers (isTableOrIdentifier, isModuleName) live only on the
 * concrete ExtraPropertyValidator: statics cannot be called through an injected
 * interface, so declaring them here would serve no caller.
 *
 * This interface is kept as a DI alias contract (→ ExtraPropertyValidator) so that
 * callers can depend on the interface rather than the concrete class.
 */
interface ExtraPropertyValidatorInterface
{
    /**
     * Validates one extra property value against its definition's validator.
     *
     * For lang-scoped fields the value may be an array keyed by id_lang;
     * each language value is validated individually.
     * Returns true on success, or an error message string on failure.
     *
     * @param mixed $value
     *
     * @return true|string
     */
    public function validateValue(ExtraPropertyDefinition $definition, mixed $value): bool|string;

    /**
     * Validates a batch of extra property values against their definitions.
     *
     * $valuesByModule is grouped like the reader output / writer input:
     * [moduleKey => [propertyName => value_or_lang_array]].
     * Definitions not present in $valuesByModule are skipped.
     * Returns true on success, or the first error message string encountered.
     *
     * @param array<string, array<string, mixed>> $valuesByModule [moduleKey => [propertyName => value]]
     * @param ExtraPropertyDefinitionCollection $definitions
     *
     * @return true|string
     */
    public function validate(array $valuesByModule, ExtraPropertyDefinitionCollection $definitions): bool|string;
}
