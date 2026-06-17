<?php

/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\EventListener;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use PrestaShop\PrestaShop\Core\ExtraProperty\Api\ExtraPropertyApiPayloadHandlerInterface;
use PrestaShop\PrestaShop\Core\ExtraProperty\Api\ExtraPropertyApiResponseInjectorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Bridges Admin API responses with the extra property system. It is the single, centralized entry point for the
 * read/write extra-property behaviour on the API (validation lives in the dedicated CQRSApiValidator subclass):
 *
 *  - persists the validated extraProperties payload (stashed by the API validator at validation time) once the
 *    core write produced the entity, resolving the new id from the response body, then
 *  - injects an `extraProperties` sub-object into the JSON response — for a single item and for each item of a
 *    paginated list.
 *
 * Running on kernel.response keeps the integration decoupled from the serializer and works uniformly for every
 * operation that returns an entity (GET item, GET list, POST/PATCH/PUT). Registered only in the Admin API kernel.
 */
class ExtraPropertyApiSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected readonly ExtraPropertyApiResponseInjectorInterface $responseInjector,
        protected readonly ExtraPropertyApiPayloadHandlerInterface $payloadHandler,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Negative priority so it runs after API Platform has produced the final JSON response body.
            KernelEvents::RESPONSE => [['onKernelResponse', -10]],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $operation = $request->attributes->get('_api_operation');
        if (!$operation instanceof HttpOperation) {
            return;
        }

        $response = $event->getResponse();
        if (!$this->isJsonEntityResponse($response)) {
            return;
        }

        $content = $response->getContent();
        if (false === $content || '' === $content) {
            return;
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return;
        }

        $resourceClass = (string) $operation->getClass();
        $uriTemplate = (string) $operation->getUriTemplate();
        $method = $operation->getMethod();
        $isCollection = $this->isCollection($operation, $decoded);

        // Persist the validated payload of a write operation, now that the entity id is in the response body.
        $pending = $request->attributes->get(ExtraPropertyApiPayloadHandlerInterface::PENDING_REQUEST_ATTRIBUTE);
        if (is_array($pending) && [] !== $pending && !$isCollection) {
            $this->payloadHandler->persist($pending, $decoded, $resourceClass, $uriTemplate, $method);
            $request->attributes->remove(ExtraPropertyApiPayloadHandlerInterface::PENDING_REQUEST_ATTRIBUTE);
        }

        // Inject extra properties into the response: a single item, or each paginated-list item.
        if ($isCollection && isset($decoded['items']) && is_array($decoded['items'])) {
            foreach ($decoded['items'] as $index => $item) {
                if (is_array($item)) {
                    $decoded['items'][$index] = $this->responseInjector->injectIntoItem($item, $resourceClass, $uriTemplate, $method);
                }
            }
        } else {
            $decoded = $this->responseInjector->injectIntoItem($decoded, $resourceClass, $uriTemplate, $method);
        }

        $response->setContent((string) json_encode($decoded));
        // Content length is recomputed when the response is sent; drop any stale value.
        $response->headers->remove('Content-Length');
    }

    protected function isJsonEntityResponse(Response $response): bool
    {
        if (Response::HTTP_OK !== $response->getStatusCode() && Response::HTTP_CREATED !== $response->getStatusCode()) {
            return false;
        }

        return str_contains((string) $response->headers->get('Content-Type', ''), 'json');
    }

    /**
     * @param array<string, mixed> $decoded
     */
    protected function isCollection(HttpOperation $operation, array $decoded): bool
    {
        return $operation instanceof CollectionOperationInterface || array_key_exists('items', $decoded);
    }
}
