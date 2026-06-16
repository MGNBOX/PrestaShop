<?php

/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Validation;

use PrestaShop\PrestaShop\Core\ExtraProperty\Definition\ExtraPropertyDefinition;
use PrestaShop\PrestaShop\Core\ExtraProperty\Definition\ExtraPropertyDefinitionCollection;
use Symfony\Contracts\Translation\TranslatorInterface;
use Validate;

/**
 * Validates extra property values against their registered definitions.
 *
 * Centralizes validation so that ObjectModel, BO form handlers and API integrations
 * all use the same rules. Structural checks (isTableOrIdentifier, isModuleName) use
 * pure regex. Value validation dispatches dynamically to Validate::xxx methods.
 *
 * Note: isRequiredWhenActive and defaultLanguageRequiredWhenActive require access to
 * the ObjectModel instance and are therefore intentionally skipped by validateValue().
 * ObjectModel-level validation handles those two cases directly.
 */
class ExtraPropertyValidator implements ExtraPropertyValidatorInterface
{
    public function __construct(
        protected readonly ?TranslatorInterface $translator = null,
    ) {
    }

    /**
     * Checks if a value is a valid SQL table/identifier token:
     * 1–64 characters (MySQL identifier limit), [a-zA-Z0-9_-] only.
     *
     * Static (not part of the interface): called by the ExtraPropertyDefinition
     * constructor, which cannot receive injected services.
     */
    public static function isTableOrIdentifier(string $value): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $value);
    }

    /**
     * Checks if a value is a valid module technical name.
     *
     * Static (not part of the interface): called by the ExtraPropertyDefinition
     * constructor, which cannot receive injected services.
     */
    public static function isModuleName(string $value): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_-]+$/', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue(ExtraPropertyDefinition $definition, mixed $value): bool|string
    {
        $validator = $definition->getValidator() ?? '';
        if ('' === $validator || !$this->hasValidatorMethod($validator)) {
            return true;
        }

        // isRequiredWhenActive / defaultLanguageRequiredWhenActive require the ObjectModel instance; skip here.
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
                if (!(bool) call_user_func([Validate::class, $validator], $langValue)) {
                    return $errorMessage;
                }
            }

            return true;
        }

        if (('' === (string) $value || null === $value) && !$isEmptyValidationMethod) {
            return true;
        }

        return (bool) call_user_func([Validate::class, $validator], $value) ? true : $errorMessage;
    }

    /**
     * Validates a set of extra property values against a list of definitions.
     *
     * Values are grouped by module then property name, like the reader output and the
     * writer input: [moduleKey => [propertyName => value_or_lang_array]].
     * Returns true on success, or the first error message string on failure.
     *
     * @param array<string, array<string, mixed>> $valuesByModule [moduleKey => [propertyName => value]]
     * @param ExtraPropertyDefinitionCollection $definitions
     *
     * @return true|string
     */
    public function validate(array $valuesByModule, ExtraPropertyDefinitionCollection $definitions): bool|string
    {
        foreach ($definitions as $definition) {
            $moduleKey = $definition->getNormalizedModuleKey();
            $propertyName = $definition->getPropertyName();
            if (!isset($valuesByModule[$moduleKey])
                || !is_array($valuesByModule[$moduleKey])
                || !array_key_exists($propertyName, $valuesByModule[$moduleKey])
            ) {
                continue;
            }

            $result = $this->validateValue($definition, $valuesByModule[$moduleKey][$propertyName]);
            if (true !== $result) {
                return $result;
            }
        }

        return true;
    }

    protected function hasValidatorMethod(string $validator): bool
    {
        return '' !== $validator && method_exists(Validate::class, $validator);
    }
}
