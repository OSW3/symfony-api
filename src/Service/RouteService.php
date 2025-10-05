<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ConfigurationService;
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
        foreach ($providers as $provider) 
        foreach ($provider['collections'] ?? [] as $entityOptions) 
        foreach ($entityOptions['endpoints'] ?? [] as $endpointName => $endpointOption) 
        {
            // Route Name
            $name = $endpointOption['name'];

            // Route path
            $prefix = $entityOptions['route']['prefix'];
            $collection   = $entityOptions['name'];
            $path   = "{$prefix}/{$collection}";
            foreach ($endpointOption['options'] ?? [] as $opt) $path .= "/{{$opt}}";

            // Route defaults
            $defaults = [];
            $defaults['_controller'] = $endpointOption['controller'] ?? null;

            // Route requirements
            $requirements = $endpointOption['requirements'] ?? [];

            // Route options
            $options = $endpointOption['options'] ?? [];

            // Route host
            $host = $endpointOption['host'] ?? null;

            // Route schemes
            $schemes = $endpointOption['schemes'] ?? [];

            // Route methods
            $methods = $endpointOption['methods'] ?? [];

            // Route conditions
            $conditions = $endpointOption['conditions'] ?? null;

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
                    'conditions'   => $conditions,
                ];
            }
        }

        return $routes;
    }
}