<?php 
namespace OSW3\Api\Subscribers;

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
        private readonly DeprecationService $deprecationService,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 32],
            KernelEvents::RESPONSE => ['onResponse', 0],
        ];
    }
    
    public function onRequest(RequestEvent $event): void 
    {
        if (!$event->isMainRequest() || !$this->deprecationService->isRemoved()) {
            return;
        }

        $response = new JsonResponse();

        $response->setStatusCode(Response::HTTP_GONE);

        $response->setContent('{"error": "This endpoint has been removed."}');

        $this->applyDeprecationHeaders($response);

        $event->setResponse($response);
        $event->stopPropagation();
        return;
    }
    
    public function onResponse(ResponseEvent $event): void 
    {
        if (!$event->isMainRequest() || !$this->deprecationService->isDeprecated()) {
            return;
        }

        $response = $event->getResponse();

        // $response->setStatusCode(Response::HTTP_UPGRADE_REQUIRED);
        $response->setStatusCode(Response::HTTP_OK);

        $this->applyDeprecationHeaders($response);
    }

    private function applyDeprecationHeaders(Response $response): void
    {
        $start   = $this->deprecationService->getStartDate();
        $sunset  = $this->deprecationService->getSunsetDate();
        $link    = $this->deprecationService->getLink();
        $message = $this->deprecationService->getMessage();
        $reason  = $this->deprecationService->getReason();

        $response->headers->set(
            DeprecationService::HEADER_DEPRECATION,
            $start ? $start->format(DATE_RFC7231) : 'true'
        );

        if ($sunset) {
            $response->headers->set(
                DeprecationService::HEADER_SUNSET,
                $sunset->format(DATE_RFC7231)
            );
        }

        if ($link) {
            $response->headers->set(
                DeprecationService::HEADER_LINK,
                sprintf('<%s>; rel="successor-version"', $link)
            );
        }

        if ($reason) {
            $response->headers->set(
                DeprecationService::HEADER_WARNING,
                sprintf('299 - "%s"', $reason)
            );
        }

        if ($message) {
            $response->headers->set(
                DeprecationService::HEADER_MESSAGE,
                $message
            );
        }
    }
}