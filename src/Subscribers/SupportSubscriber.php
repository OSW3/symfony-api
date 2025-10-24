<?php 
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\RouteService;
use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\RepositoryService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SupportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RouteService $routeService,
        private readonly RequestService $requestService,
        private readonly RepositoryService $repositoryService,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            // KernelEvents::REQUEST => ['onKernelRequest', 20]
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }



        if (in_array($event->getRequest()->attributes->get('_api_endpoint'), ['register', 'login'], true)) {
            return;
        }


        $routeName = $event->getRequest()->attributes->get('_route');
        // dd($routeName);

        // Is _route parameter is defined ?
        if (!$routeName) {
            dd('not defined route');
            return;
        }

        // Is route is defined in the config
        if (!$this->routeService->isRegisteredRoute($routeName)) {
            dd('not registered route');
            return;
        }

        // Is the HTTP Method supported
        if (!$this->routeService->isMethodSupported($routeName)) {
            // dd('not supported method');
            throw new \Exception("Method not supported for the route {$routeName}");
            // return;
        }

        // // Is the collection repository callable
        // if (!$this->repositoryService->isRepositoryCallable()) {
        //     $repository = $this->repositoryService->resolveRepositoryClass();
        //     $method     = $this->repositoryService->resolveRepositoryMethod();
        //     dd('not callable repository');
        //     throw RepositoryCallException::invalid($repository, $method);
        // }

        // // Has requirements params
        // if (!$this->requestService->hasRequiredParams()) {
        //     dd('not has required params');
        //     return;
        // }

        // dd([
        //     __CLASS__,
        //     '---',
        // ]);
    }
}