<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Storage;

use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\QueryResult\ExtraPropertyDefinitionInfo;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyNaming;
use PrestaShop\PrestaShop\Core\ExtraProperty\Validation\ExtraPropertyValidationInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates extra property values against their registered definitions.
 *
 * Centralizes validation so that ObjectModel, BO form handlers and API integrations
 * all use the same rules. Each call to validate() or validateValue() applies
 * the validator method declared in the definition.
 *
 * Note: the validators isRequiredWhenActive and defaultLanguageRequiredWhenActive need
 * access to the ObjectModel instance and are therefore intentionally skipped by this
 * service. ObjectModel-level validation should handle those two cases directly.
 */
class ExtraPropertyValueValidator
{
    public function __construct(
        protected readonly ?TranslatorInterface $translator = null,
        protected readonly ?ExtraPropertyValidationInterface $validatorAdapter = null,
    ) {
    }

    /**
     * Validates a set of extra property values against a list of definitions.
     *
     * Values use the flat storage-column format: ['module_field' => value_or_lang_array].
     * Returns true on success, or the first error message string on failure.
     *
     * @param array<string, mixed> $flatValues column_name => value
     * @param list<ExtraPropertyDefinitionInfo> $definitions
     *
     * @return true|string
     */
    public function validate(array $flatValues, array $definitions): bool|string
    {
        foreach ($definitions as $definition) {
            $columnName = ExtraPropertyNaming::storageColumnName(
                $definition->getModuleName() ?? '',
                $definition->getPropertyName()
            );
            if (!array_key_exists($columnName, $flatValues)) {
                continue;
            }

            $result = $this->validateValue($definition, $flatValues[$columnName]);
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
        if ('' === $validator || null === $this->validatorAdapter || !$this->validatorAdapter->hasValidator($validator)) {
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
                if (!$this->validatorAdapter->validateByName($validator, $langValue)) {
                    return $errorMessage;
                }
            }

            return true;
        }

        if (('' === (string) $value || null === $value) && !$isEmptyValidationMethod) {
            return true;
        }

        return $this->validatorAdapter->validateByName($validator, $value) ? true : $errorMessage;
    }
}
