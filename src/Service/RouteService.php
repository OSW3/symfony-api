<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ConfigurationService;
use OSW3\Api\Controller\PlaceholderController;
use Symfony\Component\Routing\RouterInterface;

final class RouteService 
{
    public function __construct(
        private ConfigurationService $configuration,
        private RouterInterface $routerInterface,
    ){}

    public function getExposedRoutes(): array
    {
        $providers = $this->configuration->getAllProviders();
        $routes = [];


        // Read APIs definitions
        // my_custom_api_v1, my_custom_api_v2, ...
        foreach ($providers as $provider) {
            foreach ($provider['collections'] ?? [] as $entityOptions) {
                foreach ($entityOptions['endpoints'] ?? [] as $endpointName => $endpointOption) 
                {
                    // Route Name
                    $name = $endpointOption['route']['name'];

                    // Route path
                    $prefix     = $entityOptions['route']['prefix'];
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
        
                    // Route schemes
                    $schemes = $endpointOption['route']['schemes'] ?? [];
        
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
}