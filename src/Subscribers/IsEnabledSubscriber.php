<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\ContextService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class IsEnabledSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ContextService $contextService,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            // KernelEvents::REQUEST => ['onRequest', 32]
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();
        $routeName  = $event->getRequest()->attributes->get('_route');
        
        // dd($event->isMainRequest(), $provider, $collection, $endpoint, $routeName, !in_array(null, [$provider, $collection, $endpoint, $routeName], true));
        // If all are defined, we cannot determine the isEnabled status
        if (!in_array(null, [$provider, $collection, $endpoint, $routeName], true)) {
            return;
        }
        // throw new NotFoundHttpException('Page not found.');

        // Create a new response
        $response = new JsonResponse();

        // Set the response status code
        $response->setStatusCode(Response::HTTP_NOT_FOUND);

        // Set the response content
        $content = '{"error": "This route is not available."}';
        $response->setContent($content);

        // Stop the event propagation
        $event->setResponse($response);
        $event->stopPropagation();
    }
}