<?php 
namespace OSW3\Api\Service;

use Symfony\Component\HttpFoundation\Request;
use OSW3\Api\Exception\RepositoryCallException;
use Symfony\Component\HttpFoundation\RequestStack;

final class SupportService 
{
    // private readonly Request $request;

    public function __construct(
        // private readonly RequestStack $requestStack,
        private readonly RouteService $routeService,
        private readonly RequestService $requestService,
        private readonly SecurityService $securityService,
        private readonly RepositoryService $repositoryService,
    ){
        // $this->request = $requestStack->getCurrentRequest();
    }

    public function supports(): bool 
    {
        $routeName = $this->requestService->getCurrentRoute();

        
        // Is _route parameter is defined ?
        if (!$routeName) {
            return false;
        }
        
        // Is route is defined in the config
        if (!$this->routeService->isRegisteredRoute($routeName)) {
            return false;
        }

        // Is the HTTP Method supported
        if (!$this->routeService->isMethodSupported($routeName)) {
            return false;
        }

        // Is the collection repository callable
        if (!$this->repositoryService->isRepositoryCallable()) {
            $repository = $this->repositoryService->getRepositoryClass();
            $method     = $this->repositoryService->getRepositoryMethod();
            throw RepositoryCallException::invalid($repository, $method);

        }

        // Has requirements params
        if (!$this->requestService->hasRequiredParameters()) {
            return false;
        }

        return true;
    }


}