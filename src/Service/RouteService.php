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
        private readonly RequestStack $requestStack,
        private readonly ConfigurationService $configuration,
    ){
        $this->request = $requestStack->getCurrentRequest();
    }


    // Route Information from ConfigurationService

    public function getPattern(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): string|null
    {
        $pattern = $this->configuration->getRouteNamePattern(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint,
        );

        return $pattern;
    }

    public function getPrefix(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): string|null
    {
        $pattern = $this->configuration->getRoutePrefix(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint,
        );

        return $pattern;
    }

    /**
     * Get the route name for a given provider, collection, and endpoint
     * e.g.: $this->getName('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return string|null
     */
    public function getName(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): string|null
    {
        $name = $this->configuration->getRouteName(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint,
        );

        $name = preg_replace('/-/', '_', $name);

        return $name;
    }

    /**
     * Get the route path for a given provider, collection, and endpoint
     * e.g.: $this->getPath('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return string|null
     */
    public function getPath(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): string|null
    {
        $path =$this->configuration->getRoutePath(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint,
        );

        $options = $this->getOptions(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint,
        );

        if (isset($options['context'])) 
            unset($options['context']); 

        foreach ($options ?? [] as $opt) 
            $path .= "/{{$opt}}";

        return $path;
    }

    /**
     * Get the route requirements for a given provider, collection, and endpoint
     * e.g.: $this->getRequirements('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     *
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return array
     */
    public function getRequirements(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): array
    {
        return $this->configuration->getRouteRequirements(
                provider   : $provider,
                segment    : $segment,
                collection : $collection,
                endpoint   : $endpoint, 
            ) ?? [];
    }

    /**
     * Get the route options for a given provider, collection, and endpoint
     * e.g.: $this->getOptions('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     *
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return array
     */
    public function getOptions(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): array
    {
        $options = $this->configuration->getRouteOptions(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint,
        ) ?? [];

        $options['context'] = [
            'provider'   => $provider,
            'segment'    => $segment,
            'collection' => $collection,
            'endpoint'   => $endpoint,
        ];

        return $options;
    }

    /**
     * Get the route hosts for a given provider, collection, and endpoint
     * e.g.: $this->getHosts('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     *
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return string|null
     */
    public function getHosts(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): array
    {
        $hosts = $this->configuration->getRouteHosts(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint, 
        ) ?? [];

        return $hosts;
    }

    public function getHost(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): string|null
    {
        $hosts = $this->getHosts(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint,
        );

        return $hosts[0] ?? null;
    }

    /**
     * Get the route schemes for a given provider, collection, and endpoint
     * e.g.: $this->getSchemes('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     *
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return array
     */
    public function getSchemes(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): array
    {
        return $this->configuration->getRouteSchemes(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint, 
        ) ?? [];
    }

    /**
     * Get the route methods for a given provider, collection, and endpoint
     * e.g.: $this->getMethods('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     *
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return array|null
     */
    public function getMethods(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): array|null
    {
        return $this->configuration->getRouteMethods(
                provider   : $provider,
                segment    : $segment,
                collection : $collection,
                endpoint   : $endpoint,
            ) ?? null;
    }

    /**
     * Get the route condition for a given provider, collection, and endpoint
     * e.g.: $this->getCondition('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     *
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return string|null
     */
    public function getCondition(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): string
    {
        return $this->configuration->getRouteCondition(
                provider   : $provider,
                segment    : $segment,
                collection : $collection,
                endpoint   : $endpoint, 
            ) ?? '';
    }


    // Computed Route Information

    /**
     * Get the route defaults for a given provider, collection, and endpoint
     * e.g.: $this->getDefaults('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return array
     */
    public function getDefaults(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): array
    {
        $controller = $this->configuration->getRouteController(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint, 
        );

        $defaults = [];
        $defaults['_controller'] = $controller;
        $defaults['_context'] = [
            'provider'   => $provider,
            'segment'    => $segment,
            'collection' => $collection,
            'endpoint'   => $endpoint,
        ];
        
        return $defaults;
    }

    /**
     * Get all exposed routes from configuration
     * 
     * @return array
     */
    public function getExposedRoutes(): array
    {
        // Collect all exposed routes from configuration
        $routes = [];

        // Get all providers
        $providers = $this->configuration->getProviders();

        foreach ($providers as $provider => $options) {

            // Continue if provider is not enabled
            if (!$this->configuration->isProviderEnabled($provider)) continue;

            // Merge authentication and collection routes
            $c1 = $this->configuration->getCollections($provider, ContextService::SEGMENT_AUTHENTICATION);
            $c2 = $this->configuration->getCollections($provider, ContextService::SEGMENT_COLLECTION);
            $collections = array_merge($c1, $c2);

            // Iterate over each collection
            foreach ($collections as $collection => $options) {
                $endpoints = $options['endpoints'] ?? [];
                $segment   = in_array($collection, array_keys($c1), true)
                            ? ContextService::SEGMENT_AUTHENTICATION
                            : ContextService::SEGMENT_COLLECTION;

                // Iterate over each endpoint
                foreach ($endpoints as $endpoint => $options) {

                    if (!$this->configuration->isEndpointEnabled(
                        provider  : $provider,
                        segment   : $segment,
                        collection: $collection,
                        endpoint  : $endpoint
                    )) continue;

                    $name = $this->getName(
                        provider  : $provider,
                        segment   : $segment,
                        collection: $collection,
                        endpoint  : $endpoint
                    );

                    $path = $this->getPath(
                        provider  : $provider,
                        segment   : $segment,
                        collection: $collection,
                        endpoint  : $endpoint
                    );

                    $defaults = $this->getDefaults(
                        provider  : $provider,
                        segment   : $segment,
                        collection: $collection,
                        endpoint  : $endpoint
                    );

                    $requirements = $this->getRequirements(
                        provider  : $provider,
                        segment   : $segment,
                        collection: $collection,
                        endpoint  : $endpoint
                    );

                    $options = $this->getOptions(
                        provider  : $provider,
                        segment   : $segment,
                        collection: $collection,
                        endpoint  : $endpoint
                    );

                    $host = $this->getHost(
                        provider  : $provider,
                        segment   : $segment,
                        collection: $collection,
                        endpoint  : $endpoint
                    );

                    $schemes = $this->getSchemes(
                        provider  : $provider,
                        segment   : $segment,
                        collection: $collection,
                        endpoint  : $endpoint
                    );

                    $methods = $this->getMethods(
                        provider  : $provider,
                        segment   : $segment,
                        collection: $collection,
                        endpoint  : $endpoint
                    );

                    $condition = $this->getCondition(
                        provider  : $provider,
                        segment   : $segment,
                        collection: $collection,
                        endpoint  : $endpoint
                    );

                    $this->addRouteToCollection($routes, [
                        'name'         => $name,
                        'path'         => $path,
                        'defaults'     => $defaults,
                        'requirements' => $requirements,
                        'options'      => $options,
                        'host'         => $host,
                        'schemes'      => $schemes,
                        'methods'      => $methods,
                        'condition'    => $condition,
                    ]);
                }
            }
        }

        return $routes;
    }

    /**
     * Get the current route information
     * 
     * @return array|null
     */
    public function getCurrentRoute(): array|null 
    {
        $routeName = $this->request?->attributes->get('_route');

        if (!$routeName) {
            return null;
        }

        $exposedRoutes = $this->getExposedRoutes();

        return $exposedRoutes[$routeName] ?? null;
    }

    /**
     * Check if a given route is registered in the API configuration
     *
     * @param string $route
     * @return bool
     */
    public function isRegisteredRoute(string $route): bool 
    {
        foreach ($this->getExposedRoutes() as $k => $v) {
            if ($k === $route) return true;
        }

        return false;
    }

    /**
     * Check if the current request method is supported by the given route
     *
     * @param string $route
     * @return bool
     */
    public function isMethodSupported(string $route): bool 
    {
        $method = $this->request->getMethod();

        foreach ($this->getExposedRoutes() as $routeName => $routeOptions) {
            if ($routeName === $route && in_array($method, $routeOptions['methods'] ?? [], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add a route to the collection if it does not already exist
     *
     * @param array $route
     * @param array $options
     * @return void
     */
    private function addRouteToCollection(array &$route, array $options = []): void 
    {
        if (!isset($route[$options['name']])) {
            $route[$options['name']] = [
                'path'         => $options['path'] ?? '',
                'defaults'     => $options['defaults'] ?? [],
                'requirements' => $options['requirements'] ?? [],
                'options'      => $options['options'] ?? [],
                'host'         => $options['host'] ?? null,
                'schemes'      => $options['schemes'] ?? [],
                'methods'      => $options['methods'] ?? [],
                'condition'    => $options['condition'] ?? '',
            ];
        }
    }





    /**
     * Resolve the route name for a given provider and action
     *
     * Parse the route name pattern and replace placeholders
     * e.g.: api_{version}_{collection}_{action} -> api_v1_users_list
     * 
     * @param string $provider
     * @param string $action
     * @return string|null
     */
    // public function resolveRouteName(string $provider, string $entity, string $action): string|null 
    // {
    //     $pattern    = $this->configuration->getRouteNamePattern($provider);
    //     $version    = $this->versionService->getLabel($provider);
    //     $collection = $this->configuration->getAuthenticationName($provider, $entity);

    //     $route      = $pattern;
    //     $route      = preg_replace("/{version}/", $version, $route);
    //     $route      = preg_replace("/{action}/", $action, $route);
    //     $route      = preg_replace("/{collection}/", $collection, $route);

    //     return $route;
    // }

    /**
     * Get the context attributes of the current route
     * 
     * @return array
     */
    // public function getContext(): array
    // {
    //     $route = $this->getCurrentRoute();
    //     $options = $route ? $route['options'] : [];

    //     return $options['context'] ?? [];
    // }


    /**
     * Get the route controller for a given provider, collection, and endpoint
     *
     * @param string $provider
     * @param string $collection
     * @param string $endpoint
     * @return string|null
     */
    // public function getController(string $provider, string $collection, string $endpoint): string|null
    // {
    //     return $this->configuration->getRouteController(
    //         provider   : $provider,
    //         collection : $collection,
    //         endpoint   : $endpoint, 
    //     ) ?? null;
    // }
}