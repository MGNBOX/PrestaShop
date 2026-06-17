<?php

/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShopBundle\ApiPlatform\ExtraProperties;

use PrestaShop\PrestaShop\Core\Context\ShopContext;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;
use PrestaShop\PrestaShop\Core\ExtraProperty\Api\ExtraPropertyApiPayloadHandlerInterface;
use PrestaShop\PrestaShop\Core\ExtraProperty\Definition\ExtraPropertyDefinition;
use PrestaShop\PrestaShop\Core\ExtraProperty\Definition\ExtraPropertyDefinitionCollection;
use PrestaShop\PrestaShop\Core\ExtraProperty\Definition\ExtraPropertyDefinitionRepositoryInterface;
use PrestaShop\PrestaShop\Core\ExtraProperty\Definition\ExtraPropertyScope;
use PrestaShop\PrestaShop\Core\ExtraProperty\Validation\ExtraPropertyValidatorInterface;
use PrestaShop\PrestaShop\Core\ExtraProperty\Value\ExtraPropertyWriterInterface;
use PrestaShopBundle\ApiPlatform\Exception\LocaleNotFoundException;
use PrestaShopBundle\ApiPlatform\LocalizedValueUpdater;
use PrestaShopBundle\ApiPlatform\Metadata\LocalizedValue;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Write-side bridge: validates and persists an incoming extraProperties payload.
 *
 * Definitions are matched by associatedApis against the current operation (URI template + HTTP method), so a
 * payload is only ever written to the entities a definition explicitly targets. LANG-scope values are converted
 * from locale strings to id_lang via LocalizedValueUpdater; SHOP-scope values are routed per shop id.
 */
class ExtraPropertyApiPayloadHandler implements ExtraPropertyApiPayloadHandlerInterface
{
    public function __construct(
        protected readonly ExtraPropertyDefinitionRepositoryInterface $repository,
        protected readonly ExtraPropertyWriterInterface $writer,
        protected readonly ExtraPropertyValidatorInterface $validatorAdapter,
        protected readonly ShopContext $shopContext,
        protected readonly LocalizedValueUpdater $localizedValueUpdater,
        protected readonly ApiResourceIdResolver $idResolver,
    ) {
    }

    public function validate(array $extraPropertiesByModule, string $uriTemplate, string $method): ConstraintViolationListInterface
    {
        $violations = new ConstraintViolationList();
        if (empty($extraPropertiesByModule)) {
            return $violations;
        }

        $definitions = $this->repository->getAllDefinitions()->filterByApi($uriTemplate, $method);
        foreach ($definitions as $definition) {
            $moduleKey = $definition->getNormalizedModuleKey();
            $fieldName = $definition->getPropertyName();
            if (!isset($extraPropertiesByModule[$moduleKey]) || !array_key_exists($fieldName, $extraPropertiesByModule[$moduleKey])) {
                continue;
            }

            $value = $extraPropertiesByModule[$moduleKey][$fieldName];
            $basePath = sprintf('extraProperties.%s.%s', $moduleKey, $fieldName);

            if (ExtraPropertyScope::LANG === $definition->getScope() && is_array($value)) {
                foreach ($value as $locale => $localeValue) {
                    $this->validateOneValue($violations, $definition, $localeValue, $basePath . '.' . (string) $locale);
                }
                $this->assertKnownLocales($violations, $value, $fieldName, $basePath);
                continue;
            }

            if (ExtraPropertyScope::SHOP === $definition->getScope() && is_array($value)) {
                foreach ($value as $shopId => $shopValue) {
                    $this->validateOneValue($violations, $definition, $shopValue, $basePath . '.' . (string) $shopId);
                }
                continue;
            }

            $this->validateOneValue($violations, $definition, $value, $basePath);
        }

        return $violations;
    }

    public function persist(array $extraPropertiesByModule, array $normalizedItem, string $resourceClass, string $uriTemplate, string $method): void
    {
        if (empty($extraPropertiesByModule)) {
            return;
        }

        $definitions = $this->repository->getAllDefinitions()->filterByApi($uriTemplate, $method);
        if ($definitions->isEmpty()) {
            return;
        }

        foreach ($this->groupByEntity($definitions) as $entityName => $entityDefinitions) {
            $entityId = $this->idResolver->resolveId($normalizedItem, $entityName, $resourceClass);
            if ($entityId <= 0) {
                continue;
            }

            [$mainValuesByModule, $shopValuesByShopId] = $this->buildWritableValues($entityDefinitions, $extraPropertiesByModule);
            $primaryKeyName = $entityDefinitions->first()->getPrimaryKeyName();

            if (!empty($mainValuesByModule)) {
                $this->writer->writeAll($entityName, $primaryKeyName, $entityId, $mainValuesByModule, $this->shopContext->getShopConstraint());
            }

            foreach ($shopValuesByShopId as $shopId => $valuesByModule) {
                $this->writer->writeAll($entityName, $primaryKeyName, $entityId, $valuesByModule, ShopConstraint::shop((int) $shopId));
            }
        }
    }

    /**
     * Splits the payload into the main grouped write (common + lang values, locale keys converted to id_lang)
     * and per-shop grouped writes (shop-scoped values keyed by shop id in the payload).
     *
     * @param array<string, array<string, mixed>> $extraPropertiesByModule
     *
     * @return array{0: array<string, array<string, mixed>>, 1: array<int, array<string, array<string, mixed>>>}
     */
    protected function buildWritableValues(ExtraPropertyDefinitionCollection $definitions, array $extraPropertiesByModule): array
    {
        $mainValuesByModule = [];
        $shopValuesByShopId = [];

        foreach ($definitions as $definition) {
            $moduleKey = $definition->getNormalizedModuleKey();
            $fieldName = $definition->getPropertyName();
            if (!isset($extraPropertiesByModule[$moduleKey][$fieldName])) {
                continue;
            }

            $value = $extraPropertiesByModule[$moduleKey][$fieldName];

            if (ExtraPropertyScope::LANG === $definition->getScope()) {
                if (!is_array($value)) {
                    continue;
                }
                try {
                    $byIdLang = $this->localizedValueUpdater->denormalizeLocalizedValue(
                        $value,
                        $fieldName,
                        [LocalizedValue::IS_LOCALIZED_VALUE => true, LocalizedValue::DENORMALIZED_KEY => LocalizedValue::ID_KEY],
                    );
                } catch (LocaleNotFoundException) {
                    // Unknown locales are reported as violations during validate(); skip defensively here.
                    continue;
                }
                $byIdLang = is_array($byIdLang)
                    ? array_filter($byIdLang, static fn ($key): bool => is_int($key), ARRAY_FILTER_USE_KEY)
                    : [];
                if ([] !== $byIdLang) {
                    $mainValuesByModule[$moduleKey][$fieldName] = $byIdLang;
                }
            } elseif (ExtraPropertyScope::SHOP === $definition->getScope()) {
                if (!is_array($value)) {
                    continue;
                }
                foreach ($value as $shopId => $shopValue) {
                    $shopValuesByShopId[(int) $shopId][$moduleKey][$fieldName] = $shopValue;
                }
            } else {
                $mainValuesByModule[$moduleKey][$fieldName] = $value;
            }
        }

        return [$mainValuesByModule, $shopValuesByShopId];
    }

    /**
     * Adds a violation when a LANG-scope payload uses a locale that does not exist in the shop.
     *
     * @param array<int|string, mixed> $localizedValue
     */
    protected function assertKnownLocales(ConstraintViolationListInterface $violations, array $localizedValue, string $fieldName, string $basePath): void
    {
        try {
            $this->localizedValueUpdater->denormalizeLocalizedValue(
                $localizedValue,
                $fieldName,
                [LocalizedValue::IS_LOCALIZED_VALUE => true, LocalizedValue::DENORMALIZED_KEY => LocalizedValue::ID_KEY],
            );
        } catch (LocaleNotFoundException $e) {
            $violations->add(new ConstraintViolation($e->getMessage(), $e->getMessage(), [], null, $basePath, $localizedValue));
        }
    }

    protected function validateOneValue(ConstraintViolationListInterface $violations, ExtraPropertyDefinition $definition, mixed $value, string $propertyPath): void
    {
        if (null === $definition->getValidator()) {
            return;
        }

        $result = $this->validatorAdapter->validateValue($definition, $value);
        if (true !== $result) {
            $message = is_string($result) && '' !== $result ? $result : 'This value is not valid.';
            $violations->add(new ConstraintViolation($message, $message, [], null, $propertyPath, $value));
        }
    }

    /**
     * @return array<string, ExtraPropertyDefinitionCollection>
     */
    protected function groupByEntity(ExtraPropertyDefinitionCollection $definitions): array
    {
        $byEntity = [];
        foreach ($definitions as $definition) {
            $byEntity[$definition->getEntityName()][] = $definition;
        }

        return array_map(
            static fn (array $defs): ExtraPropertyDefinitionCollection => new ExtraPropertyDefinitionCollection($defs),
            $byEntity
        );
    }
}
