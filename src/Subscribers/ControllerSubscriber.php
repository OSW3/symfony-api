<?php 
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\RequestService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ControllerSubscriber implements EventSubscriberInterface 
{
    public function __construct(
        private RequestService $requestService
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
        // KernelEvents::CONTROLLER => ['onController', 100],
        ];
    }

    public function onController(ControllerEvent $event): void 
    {
    
        // $request = $event->getRequest();
        // dd($request->attributes->get('_route'));


        // dd($event);

        // if (!$this->requestService->support()) {
        //     return;
        // }


    }
    
    public function onResponse(ResponseEvent $event): void
    {

        // dd($event);
        // if (!$this->requestService->support()) {
        //     return;
        // }


        // $content = [];
        // $statusCode = 200;
        // $response = new JsonResponse($content, $statusCode);


        // // Set Response
        // // --

        // $event->setResponse($response);
    }
}