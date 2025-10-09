<?php 
namespace OSW3\Api\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestService 
{
    private readonly Request $request;

    public function __construct(
        private ConfigurationService $configuration,
        private RequestStack $requestStack,
    ){
        $this->request = $requestStack->getCurrentRequest();
    }



    public function hasRequiredParams(): bool 
    {
        $provider    = $this->configuration->guessProvider();
        $collection  = $this->configuration->guessCollection();
        $endpoint    = $this->configuration->guessEndpoint();
        $required    = $this->configuration->getEndpointRouteRequirements($provider, $collection, $endpoint);
        $routeParams = $this->request->attributes->get('_route_params', []);

        foreach (array_keys($required) as $param) {
            if (!array_key_exists($param, $routeParams)) {
                return false;
            }
        }

        return true;
    }




    public function getParams(): array
    {
        return array_merge(
            $this->request->query->all(),
            $this->request->request->all(),
            $this->request->attributes->all()
        );
    }





    public function getEntityClassname(): string|null
    {
        // $providers = $this->configuration->getAllProviders();
        // $current = $this->requestStack->getCurrentRequest()->get('_route');

        // foreach ($providers as $provider) 
        // foreach ($provider['collections'] ?? [] as $entityName => $collections) 
        // foreach ($collections['endpoints'] ?? [] as $endpointName => $endpointOption) 
        // {
        //     $routeName = $endpointOption['name'];

        //     if ($current == $routeName) {
        //         return $entityName;
        //     }
        // }
        
        return null;
    }

    /**
     * Get the repository method
     *
     * @return string|null
     */
    public function getRepositoryMethod(): string|null 
    {
        // $providers = $this->configuration->getAllProviders();
        // $current = $this->requestStack->getCurrentRequest()->get('_route');

        // foreach ($providers as $provider) 
        // foreach ($provider['collections'] ?? [] as $entityName => $collections) 
        // foreach ($collections['endpoints'] ?? [] as $endpointName => $endpointOption) 
        // {
        //     $routeName = $endpointOption['name'];

        //     if ($current == $routeName) {
        //         return $endpointOption['repository']['method'];
        //     }
        // }

        return null;
    }
       
    public function getRepositoryCriteria(): array
    {
        // $providers = $this->configuration->getAllProviders();
        // $current = $this->requestStack->getCurrentRequest()->get('_route');

        // foreach ($providers as $provider) 
        // foreach ($provider['collections'] ?? [] as $entityName => $collections) 
        // foreach ($collections['endpoints'] ?? [] as $endpointName => $endpointOption) 
        // {
        //     $routeName = $endpointOption['name'];

        //     if ($current == $routeName) {
        //         return $endpointOption['repository']['criteria'];
        //     }
        // }

        return [];
    }

    public function getSorter(): array 
    {
        return [];
    }
}