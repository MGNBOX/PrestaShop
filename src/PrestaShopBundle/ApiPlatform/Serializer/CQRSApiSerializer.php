<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

namespace PrestaShopBundle\ApiPlatform\Serializer;

use ApiPlatform\Metadata\HttpOperation;
use PrestaShopBundle\ApiPlatform\ContextParametersProvider;
use PrestaShopBundle\ApiPlatform\ExtraProperties\ExtraPropertiesApiService;
use PrestaShopBundle\ApiPlatform\LocalizedValueUpdater;
use PrestaShopBundle\ApiPlatform\Metadata\LocalizedValue;
use PrestaShopBundle\ApiPlatform\NormalizationMapper;
use PrestaShopBundle\ApiPlatform\PositionCollectionUpdater;
use ReflectionNamedType;
use Symfony\Component\Serializer\Encoder\ContextAwareDecoderInterface;
use Symfony\Component\Serializer\Encoder\ContextAwareEncoderInterface;
use Symfony\Component\Serializer\Exception\UnsupportedFormatException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * This serializer decorates the API Platform one, it handles PrestaShop custom modifications like updating the localized values indexes,
 * or apply the mapping between CQRS object and API resources.
 *
 * Extra-properties support is activated by injecting an ExtraPropertiesApiService instance.
 * The dependency is optional (null by default) to preserve backward compatibility with
 * subclasses that call parent::__construct() with the original argument list.
 */
class CQRSApiSerializer implements SerializerInterface, ContextAwareNormalizerInterface, ContextAwareDenormalizerInterface, ContextAwareEncoderInterface, ContextAwareDecoderInterface
{
    public const CAST_BOOL = 'cast_bool';

    /**
     * CQRS command namespaces that must be excluded from extra-properties processing.
     * Denormalization of these types is for query/command objects, not ApiResources.
     */
    protected const CQRS_DOMAIN_NAMESPACE_PREFIX = 'PrestaShop\PrestaShop\Core\Domain\\';

    public function __construct(
        protected readonly Serializer $decorated,
        protected readonly ContextParametersProvider $contextParametersProvider,
        protected readonly ClassMetadataFactoryInterface $classMetadataFactory,
        protected readonly LocalizedValueUpdater $localizedValueUpdater,
        protected readonly NormalizationMapper $normalizationMapper,
        protected readonly PositionCollectionUpdater $positionCollectionUpdater,
        protected readonly ?ExtraPropertiesApiService $extraPropertiesApiService = null,
    ) {
    }

    public function supportsDecoding(string $format, array $context = []): bool
    {
        return $this->decorated->supportsDecoding($format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $this->decorated->supportsDenormalization($data, $type, $format, $context);
    }

    public function supportsEncoding(string $format, array $context = []): bool
    {
        return $this->decorated->supportsEncoding($format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->decorated->supportsNormalization($data, $format, $context);
    }

    public function decode(string $data, string $format, array $context = [])
    {
        // Usually empty body would trigger an exception, unless we allowed it via the custom extra property
        if ($this->isEmptyBodyAllowed($data, $context)) {
            return [];
        }

        return $this->decorated->decode($data, $format, $context);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = [])
    {
        // Add context parameters and URI variables into the data
        $data = array_merge($data, $this->contextParametersProvider->getContextParameters());
        if (!empty($context['uri_variables'])) {
            $data = array_merge($data, $context['uri_variables']);
        }

        // Before anything perform the mapping if specified
        $this->normalizationMapper->mapNormalizedData($data, $context);

        // Boolean casting is only implemented in Serializer 7.1, so for now we handle it manually
        // This is important for list results mainly where boolean are returned as tiny int (0, 1)
        $this->addBooleanCastCallbacks($type, $context);

        // Update localized value to be adapted for denormalization
        if (is_array($data)) {
            $data = $this->denormalizeLocalizedValues($data, $type, $context);
        }

        // Extract extra properties from the payload before delegating to the decorated serializer.
        // Only ApiResource classes (not CQRS domain commands) are processed.
        if (
            null !== $this->extraPropertiesApiService
            && is_array($data)
            && !str_starts_with($type, static::CQRS_DOMAIN_NAMESPACE_PREFIX)
        ) {
            $entityTable = $this->extraPropertiesApiService->resolveEntityTableFromClass($type);
            if (null !== $entityTable) {
                $extraProperties = $this->extraPropertiesApiService->extractExtraPropertiesFromPayload($data);
                if (null !== $extraProperties) {
                    $violations = $this->extraPropertiesApiService->validateExtraPropertiesPayload($entityTable, $extraProperties);
                    if (count($violations) > 0) {
                        throw new ValidationFailedException(null, $violations);
                    }
                    $this->extraPropertiesApiService->storePendingExtraProperties($entityTable, $extraProperties);
                }
            }
        }

        return $this->decorated->denormalize($data, $type, $format, $context);
    }

    public function encode(mixed $data, string $format, array $context = []): string
    {
        return $this->decorated->encode($data, $format, $context);
    }

    public function normalize(mixed $object, ?string $format = null, array $context = [])
    {
        // First let the usual serializer and normalizers do their job
        $normalizedData = $this->decorated->normalize($object, $format, $context);

        // Then update the localized values to use the appropriate indexes
        if (is_object($object) && class_exists(get_class($object))) {
            $normalizedData = $this->normalizeLocalizedValues($normalizedData, get_class($object), $context);
            $normalizedData = $this->positionCollectionUpdater->normalizePositionCollection($normalizedData, get_class($object));
        }

        // Finally perform normalization mapping
        $this->normalizationMapper->mapNormalizedData($normalizedData, $context);

        // Inject extra properties into the serialized response (and persist pending ones for write ops).
        // Uses the resource_class from context when available (more reliable than get_class($object)
        // which may return an intermediate DTO class rather than the declared ApiResource class).
        if (
            null !== $this->extraPropertiesApiService
            && is_array($normalizedData)
            && is_object($object)
        ) {
            $resourceClass = $context['resource_class'] ?? get_class($object);
            if (!str_starts_with($resourceClass, static::CQRS_DOMAIN_NAMESPACE_PREFIX)) {
                $normalizedData = $this->extraPropertiesApiService->injectExtraPropertiesIntoResponse(
                    $normalizedData,
                    $resourceClass
                );
            }
        }

        return $normalizedData;
    }

    public function serialize(mixed $data, string $format, array $context = []): string
    {
        // Do not delegate directly to the decorated serializer here:
        // we need to pass through this class normalize() implementation so that
        // PrestaShop-specific mappings (localized values, position collections, extraProperties, ...)
        // are applied before encoding.
        $normalized = $this->normalize($data, $format, $context);

        return $this->encode($normalized, $format, $context);
    }

    public function deserialize(mixed $data, string $type, string $format, array $context = []): mixed
    {
        if (!$this->supportsDecoding($format, $context)) {
            throw new UnsupportedFormatException(sprintf('Deserialization for the format "%s" is not supported.', $format));
        }

        $data = $this->decode($data, $format, $context);

        return $this->denormalize($data, $type, $format, $context);
    }

    /**
     * Denormalize data for localized values so that the indexes match the expected value (ID or locale)
     */
    protected function denormalizeLocalizedValues(array $data, string $type, array $context = []): array
    {
        $localizedAttributesContext = $this->localizedValueUpdater->getLocalizedAttributesContext($type);
        if (!empty($localizedAttributesContext)) {
            foreach ($localizedAttributesContext as $parameterName => $attributeContext) {
                if (!empty($data[$parameterName])) {
                    $data[$parameterName] = $this->localizedValueUpdater->denormalizeLocalizedValue(
                        $data[$parameterName],
                        $parameterName,
                        $context + [LocalizedValue::IS_LOCALIZED_VALUE => true] + $attributeContext
                    );
                }
            }
        }

        return $data;
    }

    /**
     * Normalize data for localized values so that the indexes match the expected value (ID or locale)
     */
    protected function normalizeLocalizedValues(array $data, string $type, array $context = []): array
    {
        $localizedAttributesContext = $this->localizedValueUpdater->getLocalizedAttributesContext($type);
        if (!empty($localizedAttributesContext)) {
            foreach ($localizedAttributesContext as $parameterName => $attributeContext) {
                if (!empty($data[$parameterName])) {
                    $data[$parameterName] = $this->localizedValueUpdater->normalizeLocalizedValue(
                        $data[$parameterName],
                        $parameterName,
                        $context + [LocalizedValue::IS_LOCALIZED_VALUE => true] + $attributeContext
                    );
                }
            }
        }

        return $data;
    }

    /**
     * Force casting boolean properties so that values like (1, 0, true, on, false, ...) are valid, this is useful for
     * data coming from DB where boolean are returned as tiny integers. To enable this casting the CQRSApiSerializer::CAST_BOOL
     * context option must be true.
     *
     * Note: in Symfony 7.1 a new option AbstractNormalizer::FILTER_BOOL has been introduced, when we upgrade our
     * Symfony dependencies our custom casting (inspired by the Symfony one) can be removed.
     *
     * https://symfony.com/doc/7.1/serializer.html#handling-boolean-values
     */
    protected function addBooleanCastCallbacks(string $type, array &$context): void
    {
        if (empty($context[self::CAST_BOOL])) {
            return;
        }

        if (!$this->classMetadataFactory->hasMetadataFor($type)) {
            return;
        }

        $metadata = $this->classMetadataFactory->getMetadataFor($type);
        foreach ($metadata->getAttributesMetadata() as $attributeMetadata) {
            if (!$metadata->getReflectionClass()->hasProperty($attributeMetadata->getName())) {
                continue;
            }

            $reflectionProperty = $metadata->getReflectionClass()->getProperty($attributeMetadata->getName());
            if ($reflectionProperty->getType() instanceof ReflectionNamedType && $reflectionProperty->getType()->getName() === 'bool') {
                $context[AbstractNormalizer::CALLBACKS][$attributeMetadata->getName()] = fn (mixed $value): bool => filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
        }
    }

    /**
     * Empty body is not allowed with JSON format as empty string is considered invalid JSON, but in some cases we
     * want to send an empty body (delete an operation, enable an entity via dedicated endpoint, ...) if the ID is
     * already in the URI, and we don't need any other data.
     */
    protected function isEmptyBodyAllowed(string $data, array $context): bool
    {
        if (!empty($data)) {
            return false;
        }

        if ($context['operation'] instanceof HttpOperation) {
            $extraProperties = $context['operation']->getExtraProperties();
            if ($extraProperties['allowEmptyBody'] ?? false) {
                return true;
            }
        }

        return false;
    }
}
