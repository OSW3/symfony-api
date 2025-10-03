<?php 
namespace OSW3\Api\Service;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;


final class RequestService 
{
    public function __construct(
        private ConfigurationService $configuration,
        private RequestStack $requestStack,
        private RouterInterface $routerInterface,
    ){}

    public function support(): bool 
    {
        $providers = $this->configuration->getProviders();
        $current = $this->requestStack->getCurrentRequest()->get('_route');
        $routes = [];

        foreach ($providers as $provider) 
        foreach ($provider['collections'] ?? [] as $collections) 
        foreach ($collections['endpoints'] ?? [] as $endpointName => $endpointOption) 
        {
            array_push($routes, $endpointOption['name']);
        }
        
        return in_array($current, $routes);
    }
}