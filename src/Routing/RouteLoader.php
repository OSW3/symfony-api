<?php
namespace OSW3\Api\Routing;

use OSW3\Api\Service\RouteService;
use Symfony\Component\Routing\Route;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

class RouteLoader extends Loader
{
    public function __construct(
        private RouteService $routeService,
    ){}

    public function load(mixed $resource, string $type = null): ?RouteCollection
    {
        if ($type !== 'api_routes') {
            return null;
        }

        $exposedRoutes = $this->routeService->getExposedRoutes();
        $routes = new RouteCollection();

        foreach ($exposedRoutes as $exposedRouteName => $exposedRouteOption)
        {
            $route = new Route(
                $exposedRouteOption['path'], 
                $exposedRouteOption['defaults'],
                $exposedRouteOption['requirements'], 
                $exposedRouteOption['options'], 
                $exposedRouteOption['host'], 
                $exposedRouteOption['schemes'], 
                $exposedRouteOption['methods'], 
                $exposedRouteOption['conditions']
            );

            $routes->add($exposedRouteName, $route);
        }

        return $routes;
    }

    public function supports(mixed $resource, string $type = null): bool
    {
        return $type === 'api_routes';
    }
}
