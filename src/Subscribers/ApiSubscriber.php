<?php 
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\RequestService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ApiSubscriber implements EventSubscriberInterface 
{
    public function __construct(
        private RequestService $requestService
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 0],
            KernelEvents::RESPONSE => ['onResponse', 10],
        ];
    }

    public function onRequest(RequestEvent $event): void 
    {
        // Check if the current route is defined in the API config
        if (!$this->requestService->support()) {
            return;
        }

    }
    
    public function onResponse(ResponseEvent $event): void
    {
        // Check if the current route is defined in the API config
        if (!$this->requestService->support()) 
        {
            return;
        }

        // Check if the current route has a defined in the API config
        if ($event->getRequest()->attributes->get('_controller') != null) 
        {
            return;
        }



        // dd($event);

        $content = [
            'response' => "Test"
        ];
        $statusCode = 200;
        $response = new JsonResponse($content, $statusCode);


        // Set Response
        // --

        $event->setResponse($response);
    }
}