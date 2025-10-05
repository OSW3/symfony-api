<?php 
namespace OSW3\Api\Service;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;


final class RequestService 
{
    public function __construct(
        private ConfigurationService $configuration,
        // private RequestStack $requestStack,
        // private RouterInterface $routerInterface,
    ){}

    public function support(): bool 
    {
        // $providers = $this->configuration->getAllProviders();
        // $current = $this->requestStack->getCurrentRequest()->get('_route');

        // if (!$current) {
        //     return false;
        // }

        // dd($providers);
        // foreach ($providers as $provider) {
        //     foreach ($provider['collections'] ?? [] as $collection) {
        //         foreach ($collection['endpoints'] ?? [] as $endpoint) {
        //             if (($endpoint['route']['name'] ?? null) === $current) {
        //                 return true; // ← Stop immédiatement dès qu'on trouve une correspondance
        //             }
        //         }
        //     }
        // }

        return false;

        // $routes = [];

        // foreach ($providers as $provider) 
        // foreach ($provider['collections'] ?? [] as $collections) 
        // foreach ($collections['endpoints'] ?? [] as $endpointName => $endpointOption) 
        // {
        //     array_push($routes, $endpointOption['route']['name']);
        // }
        
        // return in_array($current, $routes);
    }

    public function getEntityClassname(): string|null
    {
        $providers = $this->configuration->getAllProviders();
        $current = $this->requestStack->getCurrentRequest()->get('_route');

        foreach ($providers as $provider) 
        foreach ($provider['collections'] ?? [] as $entityName => $collections) 
        foreach ($collections['endpoints'] ?? [] as $endpointName => $endpointOption) 
        {
            $routeName = $endpointOption['name'];

            if ($current == $routeName) {
                return $entityName;
            }
        }
        
        return null;
    }

    /**
     * Get the repository method
     *
     * @return string|null
     */
    public function getRepositoryMethod(): string|null 
    {
        $providers = $this->configuration->getAllProviders();
        $current = $this->requestStack->getCurrentRequest()->get('_route');

        foreach ($providers as $provider) 
        foreach ($provider['collections'] ?? [] as $entityName => $collections) 
        foreach ($collections['endpoints'] ?? [] as $endpointName => $endpointOption) 
        {
            $routeName = $endpointOption['name'];

            if ($current == $routeName) {
                return $endpointOption['repository']['method'];
            }
        }

        return null;
    }
       
    public function getRepositoryCriteria(): array
    {
        $providers = $this->configuration->getAllProviders();
        $current = $this->requestStack->getCurrentRequest()->get('_route');

        foreach ($providers as $provider) 
        foreach ($provider['collections'] ?? [] as $entityName => $collections) 
        foreach ($collections['endpoints'] ?? [] as $endpointName => $endpointOption) 
        {
            $routeName = $endpointOption['name'];

            if ($current == $routeName) {
                return $endpointOption['repository']['criteria'];
            }
        }

        return [];
    }

    public function getSorter(): array 
    {
        return [];
    }
}