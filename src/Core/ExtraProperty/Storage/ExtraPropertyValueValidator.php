<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Storage;

use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\QueryResult\ExtraPropertyDefinitionInfo;
use Symfony\Contracts\Translation\TranslatorInterface;
use Validate;

/**
 * Validates extra property values against their registered definitions.
 *
 * Centralizes validation so that ObjectModel, BO form handlers and API integrations
 * all use the same rules. Each call to validate() or validateValue() applies the
 * Validate:: method declared in the definition's validator field.
 *
 * Note: the validators isRequiredWhenActive and defaultLanguageRequiredWhenActive need
 * access to the ObjectModel instance and are therefore intentionally skipped by this
 * service. ObjectModel-level validation should handle those two cases directly.
 */
class ExtraPropertyValueValidator
{
    public function __construct(
        protected readonly ?TranslatorInterface $translator = null,
    ) {
    }

    /**
     * Validates a set of extra property values against a list of definitions.
     *
     * Values must use the same grouped-by-module format as ExtraPropertyReader output:
     * ['module_key' => ['property_name' => value_or_lang_array]].
     * Returns true on success, or the first error message string on failure.
     *
     * @param array<string, array<string, mixed>> $valuesByModule
     * @param list<ExtraPropertyDefinitionInfo> $definitions
     *
     * @return true|string
     */
    public function validate(array $valuesByModule, array $definitions): bool|string
    {
        foreach ($definitions as $definition) {
            $moduleName = null !== $definition->getModuleName() ? $definition->getModuleName() : '_core';
            $propertyName = $definition->getPropertyName();

            if (!array_key_exists($propertyName, $valuesByModule[$moduleName] ?? [])) {
                continue;
            }

            $result = $this->validateValue($definition, $valuesByModule[$moduleName][$propertyName]);
            if (true !== $result) {
                return $result;
            }
        }

        return true;
    }

    /**
     * Validates one extra property value against its definition's validator.
     *
     * For lang-scoped fields the value should be an array keyed by id_lang;
     * each language value is validated individually.
     * Returns true on success, or an error message string on failure.
     *
     * @param mixed $value
     *
     * @return true|string
     */
    public function validateValue(ExtraPropertyDefinitionInfo $definition, mixed $value): bool|string
    {
        $validator = $definition->getValidator() ?? '';
        if ('' === $validator || !method_exists(Validate::class, $validator)) {
            return true;
        }

        // isRequiredWhenActive / defaultLanguageRequiredWhenActive require the ObjectModel
        // instance itself; those are handled at the ObjectModel level and skipped here.
        $isEmptyValidationMethod = 'isrequiredwhenactive' === strtolower($validator)
            || 'defaultlanguagerequiredwhenactive' === strtolower($validator);

        $label = $definition->getPropertyName();
        $errorMessage = null !== $this->translator
            ? $this->translator->trans('The %s field is invalid.', [$label], 'Admin.Notifications.Error')
            : sprintf('The %s field is invalid.', $label);

        if (is_array($value)) {
            foreach ($value as $langValue) {
                if (('' === (string) $langValue || null === $langValue) && !$isEmptyValidationMethod) {
                    continue;
                }
                if (!call_user_func([Validate::class, $validator], $langValue)) {
                    return $errorMessage;
                }
            }

            return true;
        }

        if (('' === (string) $value || null === $value) && !$isEmptyValidationMethod) {
            return true;
        }

        return call_user_func([Validate::class, $validator], $value) ? true : $errorMessage;
    }
}
