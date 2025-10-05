<?php 
namespace OSW3\Api\Service;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;


final class RequestService 
{
    public function __construct(
        private ConfigurationService $configuration,
        private RequestStack $requestStack,
        // private RouterInterface $routerInterface,
    ){}

    public function support(): bool 
    {
        $providers      = $this->configuration->getAllProviders();
        $currentRequest = $this->requestStack->getCurrentRequest();
        $currentRoute    = $currentRequest->get('_route');
        $currentPath    = $currentRequest->getPathInfo();
        $currentMethod  = $currentRequest->getMethod();

        foreach ($providers as $provider) {
            foreach ($provider['collections'] ?? [] as $collection) {
                foreach ($collection['endpoints'] ?? [] as $endpoint) {

                    if ($endpoint['route']['name'] === $currentRoute) {
                        return true;
                    }
                    // dump($endpoint['route']['name']);
                    // dump($currentRoute);

                    // $pathPrefix     = $collection['route']['prefix'];
                    // $pathCollection = $collection['name'];
                    // $path           = "{$pathPrefix}/{$pathCollection}";
                    // $methods        = $endpoint['route']['methods'];
                    
                    // $requirements     = $endpoint['route']['requirements'];
                    // $options     = $endpoint['route']['options'];



                    // $pathPattern = $currentRequest;
                    // foreach ($options ?? [] as $param) {
                    //     $regex = $requirements[$param] ?? '[^/]+';
                    //     $pathPattern = str_replace("{{$param}}", "($regex)", $pathPattern);
                    // }


                    // dump($currentPath);
                    // dump($path);
                    // dump($currentMethod);
                    // dump($methods);
                    // dump( $currentPath === $path );
                    // dump( in_array($currentMethod, $methods) );
                    // dd( $currentPath === $path && in_array($currentMethod, $methods) );

                    // return $currentPath === $path && in_array($currentMethod, $methods);
                }
            }
        }

        return false;
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