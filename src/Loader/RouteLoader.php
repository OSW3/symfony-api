<?php
namespace OSW3\Api\Loader;

use OSW3\Api\Service\RouteService;
use Symfony\Component\Routing\Route;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

class RouteLoader extends Loader
{
    private const ROUTE_TYPE = 'api_routes';

    public function __construct(
        private readonly RouteService $routeService,
    ){}

    public function load(mixed $resource, ?string $type = null): ?RouteCollection
    {
        // Only handle 'api_routes' type
        if ($type !== static::ROUTE_TYPE) {
            return null;
        }
        
        // Retrieve exposed routes
        $exposedRoutes = $this->routeService->getExposedRoutes();

        // dd($exposedRoutes);

        // Create a new RouteCollection
        $routes = new RouteCollection();

        // Add each exposed route to the collection
        foreach ($exposedRoutes as $name => $options){
            $route = new Route(
                path        : $options['path'],
                defaults    : $options['defaults'],
                requirements: $options['requirements'],
                options     : $options['options'],
                host        : $options['host'],
                schemes     : $options['schemes'],
                methods     : $options['methods'],
                condition   : $options['condition']
            );

            $routes->add(
                name: $name, 
                route: $route
            );
        }

        return $routes;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return $type === static::ROUTE_TYPE;
    }
}
