<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class RouteService 
{
    private readonly ?Request $request;
    
    public function __construct(
        private readonly ConfigurationService $configuration,
        private readonly RequestStack $requestStack,
        private readonly RouterInterface $routerInterface,
    ){
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getExposedRoutes(): array
    {
        $providers = $this->configuration->getAllProviders();
        $routes = [];

        

        // Read APIs definitions
        // my_custom_api_v1, my_custom_api_v2, ...
        foreach ($providers as $providerName => $provider) {
            foreach ($provider['collections'] ?? [] as $entityOptions) {
                foreach ($entityOptions['endpoints'] ?? [] as $endpointName => $endpointOption) 
                {
                    // Route Name
                    $name = $endpointOption['route']['name'];

                    // Route path
                    $prefix     = preg_replace("#/$#", "", $entityOptions['route']['prefix']);
                    $collection = $entityOptions['name'];
                    $path       = "{$prefix}/{$collection}";
                    foreach ($endpointOption['route']['options'] ?? [] as $opt) $path .= "/{{$opt}}";


                    // Route defaults
                    $defaults = [];
                    $defaults['_controller'] = $endpointOption['route']['controller'] ?? null;
                    // $defaults['_controller'] = $endpointOption['controller'] ?? 'OSW3\Api\Controller\PlaceholderController::handle';
                    // $defaults['_controller'] = $endpointOption['controller'] ??  [\OSW3\Api\Controller\PlaceholderController::class, 'handle'];

        
                    // Route requirements
                    $requirements = $endpointOption['route']['requirements'] ?? [];
        
                    // Route options
                    $options = $endpointOption['route']['options'] ?? [];
        
                    // Route host
                    $host = $endpointOption['route']['host'] ?? null;
                    // $host = 'osw3.net';
        
                    // Route schemes
                    $schemes = $endpointOption['route']['schemes'] ?? [];
                    // $schemes = ['http', 'https'];
                    // $schemes = [];
        
                    // Route methods
                    $methods = $endpointOption['route']['methods'] ?? [];
        
                    // Route conditions
                    $condition = $endpointOption['route']['condition'] ?? '';
        
                    if (!isset($routes[$name]))
                    {
                        $routes[$name] = [
                            'path'         => $path,
                            'defaults'     => $defaults,
                            'requirements' => $requirements,
                            'options'      => $options,
                            'host'         => $host,
                            'schemes'      => $schemes,
                            'methods'      => $methods,
                            'condition'    => $condition,
                        ];
                    }
                }
            }
        }

        return $routes;
    }


    public function isRegisteredRoute(string $route): bool 
    {
        $providers = $this->configuration->getAllProviders();

        foreach ($providers as $provider) {
            foreach ($provider['collections'] ?? [] as $collection) {
                foreach ($collection['endpoints'] ?? [] as $endpoint) {
                    if (($endpoint['route']['name'] ?? null) === $route) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function isMethodSupported(string $route): bool 
    {
        $providers = $this->configuration->getAllProviders();
        $method    = $this->request->getMethod();

        foreach ($providers as $provider) {
            foreach ($provider['collections'] ?? [] as $collection) {
                foreach ($collection['endpoints'] ?? [] as $endpoint) {
                    if (($endpoint['route']['name'] ?? null) === $route) {
                        $allowed = $endpoint['route']['methods'] ?? [];
                        return in_array($method, $allowed, true);
                    }
                }
            }
        }

        return false;
    }
}