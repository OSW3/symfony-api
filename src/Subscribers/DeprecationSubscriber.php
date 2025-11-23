<?php 
namespace OSW3\Api\Subscribers;

use OSW3\Api\Enum\Deprecation\Headers;
use OSW3\Api\Service\TemplateService;
use OSW3\Api\Service\DeprecationService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DeprecationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TemplateService $templateService,
        private readonly DeprecationService $deprecationService,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 32],
            KernelEvents::RESPONSE => ['onResponse', 0],
        ];
    }
    
    /**
     * API is removed
     * stop the event propagation and return 410 Gone response.
     */
    public function onRequest(RequestEvent $event): void 
    {
        if (!$event->isMainRequest() || !$this->deprecationService->isRemoved()) {
            return;
        }

        $this->templateService->setType('error');
        

        // Retrieve the response
        $response = $event->getResponse();

        // Create a new response
        $response = new JsonResponse();

        // Set the response status code
        $response->setStatusCode(Response::HTTP_GONE);

        // Apply deprecation headers
        $this->applyDeprecationHeaders($response);

        // Set the response content
        $content = '{"error": "This endpoint has been removed."}';
        $response->setContent($content);

        // Stop the event propagation
        $event->setResponse($response);
        $event->stopPropagation();
    }
    
    /**
     * API is deprecated
     * add deprecation headers to the response.
     */
    public function onResponse(ResponseEvent $event): void 
    {
        if (!$event->isMainRequest() || !$this->deprecationService->isDeprecated()) {
            return;
        }

        // Retrieve the response
        $response = $event->getResponse();

        // Set the response status code
        // $response->setStatusCode(Response::HTTP_UPGRADE_REQUIRED);
        $response->setStatusCode(Response::HTTP_OK);

        $this->applyDeprecationHeaders($response);
    }

    private function applyDeprecationHeaders(Response $response): void
    {
        $start     = $this->deprecationService->getStartAt();
        $sunset    = $this->deprecationService->getSunsetAt();
        $link      = $this->deprecationService->getLink();
        $successor = $this->deprecationService->getSuccessor();
        $message   = $this->deprecationService->getMessage();
        $links     = [];


        $value = 'true';
        if ($start) {
            if (!$sunset || $start <= $sunset) {
                $value = $start->format(DATE_RFC7231);
            }
        }
        $response->headers->set(Headers::DEPRECATION->value, $value);

        if ($sunset) {
            $response->headers->set(Headers::SUNSET->value, $sunset->format(DATE_RFC7231));
        }

        if ($link) {
            $links[] = sprintf('<%s>; rel="deprecation"', $link);
        }

        if ($successor) {
            $links[] = sprintf('<%s>; rel="successor-version"', $successor);
        }

        if ($links) {
            $response->headers->set(Headers::LINK->value, implode(', ', $links));
            // $response->headers->set(Headers::LINK->value, $links);
        }

        if ($message) {
            $response->headers->set(Headers::MESSAGE->value, $message);
        }
    }
}