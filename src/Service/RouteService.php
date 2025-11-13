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
        private readonly VersionService $versionService,
        private readonly RouterInterface $routerInterface,
        private readonly ConfigurationService $configuration,
    ){
        $this->request = $requestStack->getCurrentRequest();
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

        // Iterate over each provider
        foreach ($providers as $provider => $providerOptions) {

            if (!$this->configuration->isProviderEnabled($provider)) continue;

            // Add security routes if enabled
            // --

            foreach(array_merge(
                $providerOptions['security']['registration'] ?? [], 
                $providerOptions['security']['authentication'] ?? [], 
                $providerOptions['security']['password'] ?? [], 
            ) as $endpoint => $options) {

                // Check if endpoint is enabled
                if (!$this->configuration->isSecurityEndpointEnabled($provider, $endpoint)) continue;

                $name         = $this->resolveRouteName($provider, $endpoint);
                $path         = $this->configuration->getSecurityEndpointPath($provider, $endpoint);
                $defaults     = $this->getDefaults($provider, $this->configuration->getSecurityGroup($provider), $endpoint);
                $requirements = [];
                $options      = $this->getOptions($provider, $this->configuration->getSecurityGroup($provider), $endpoint);
                $host         = $this->configuration->getSecurityEndpointHosts($provider, $endpoint);
                $schemes      = $this->configuration->getSecurityEndpointSchemes($provider, $endpoint);
                $methods      = [Request::METHOD_POST];
                $condition    = '';

                $name = preg_replace('/-/', '_', $name);
                
                // TODO: Fix host to support multiple hosts
                $host = $host[0] ?? null;
                $defaults['_controller'] = $this->configuration->getSecurityEndpointController($provider, $endpoint);

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

            // Add collection routes if enabled
            // --

            foreach ($providerOptions['collections'] ?? [] as $collection => $entityOptions) {
                foreach ($entityOptions['endpoints'] ?? [] as $endpoint => $endpointOption) {
                    
                    // Check if endpoint is enabled
                    if (!$this->configuration->isEndpointEnabled(
                        provider   : $provider,
                        collection : $collection,
                        endpoint   : $endpoint,
                    )) continue;

                    $name         = $this->getName($provider, $collection, $endpoint);
                    $path         = $this->getPath($provider, $collection, $endpoint);
                    $defaults     = $this->getDefaults($provider, $collection, $endpoint);
                    $requirements = $this->getRequirements($provider, $collection, $endpoint);
                    $options      = $this->getOptions($provider, $collection, $endpoint);
                    $host         = $this->getHosts($provider, $collection, $endpoint);
                    $schemes      = $this->getSchemes($provider, $collection, $endpoint);
                    $methods      = $this->getMethods($provider, $collection, $endpoint);
                    $condition    = $this->getCondition($provider, $collection, $endpoint);

                    $name = preg_replace('/-/', '_', $name);
                    
                    // TODO: Fix host to support multiple hosts
                    $host = $host[0] ?? null;

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
     * Resolve the route name for a given provider and action
     *
     * Parse the route name pattern and replace placeholders
     * e.g.: api_{version}_{collection}_{action} -> api_v1_users_list
     * 
     * @param string $provider
     * @param string $action
     * @return string|null
     */
    public function resolveRouteName(string $provider, string $action): string|null 
    {
        $pattern    = $this->configuration->getRouteNamePattern($provider);
        $version    = $this->versionService->getLabel($provider);
        $collection = $this->configuration->getSecurityGroup($provider);

        $route      = $pattern;
        $route      = preg_replace("/{version}/", $version, $route);
        $route      = preg_replace("/{action}/", $action, $route);
        $route      = preg_replace("/{collection}/", $collection, $route);

        return $route;
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
     * Get the context attributes of the current route
     * 
     * @return array
     */
    public function getContext(): array
    {
        $route = $this->getCurrentRoute();
        $options = $route ? $route['options'] : [];

        return $options['context'] ?? [];
    }

    /**
     * Get the route name for a given provider, collection, and endpoint
     *
     * @param string $provider
     * @param string $collection
     * @param string $endpoint
     * @return string|null
     */
    public function getName(string $provider, string $collection, string $endpoint): string|null
    {
        return $this->configuration->getRouteName(
            provider   : $provider,
            collection : $collection,
            endpoint   : $endpoint,
        ) ?? null;
    }

    /**
     * Get the route path for a given provider, collection, and endpoint
     *
     * @param string $provider
     * @param string $collection
     * @param string $endpoint
     * @return string|null
     */
    public function getPath(string $provider, string $collection, string $endpoint): string|null
    {
        $path = $this->configuration->getRoutePath(
            provider   : $provider,
            collection : $collection,
            endpoint   : $endpoint,
        ) ?? null;

        $options = $this->getOptions($provider, $collection, $endpoint);

        if (isset($options['context'])) 
            unset($options['context']); 

        foreach ($options ?? [] as $opt) 
            $path .= "/{{$opt}}";

        return $path;
    }

    /**
     * Get the route controller for a given provider, collection, and endpoint
     *
     * @param string $provider
     * @param string $collection
     * @param string $endpoint
     * @return string|null
     */
    public function getController(string $provider, string $collection, string $endpoint): string|null
    {
        return $this->configuration->getRouteController(
            provider   : $provider,
            collection : $collection,
            endpoint   : $endpoint, 
        ) ?? null;
    }

    /**
     * Get the route hosts for a given provider, collection, and endpoint
     *
     * @param string $provider
     * @param string $collection
     * @param string $endpoint
     * @return array
     */
    public function getHosts(string $provider, string $collection, string $endpoint): array
    {
        return $this->configuration->getRouteHosts(
            provider   : $provider,
            collection : $collection,
            endpoint   : $endpoint, 
        ) ?? [];
    }

    /**
     * Get the route requirements for a given provider, collection, and endpoint
     *
     * @param string $provider
     * @param string $collection
     * @param string $endpoint
     * @return array
     */
    public function getRequirements(string $provider, string $collection, string $endpoint): array
    {
        return $this->configuration->getRouteRequirements(
            provider   : $provider,
            collection : $collection,
            endpoint   : $endpoint, 
        ) ?? [];
    }

    /**
     * Get the route options for a given provider, collection, and endpoint
     *
     * @param string $provider
     * @param string $collection
     * @param string $endpoint
     * @return array
     */
    public function getOptions(string $provider, string $collection, string $endpoint): array
    {
        $options = $this->configuration->getRouteOptions(
            provider   : $provider,
            collection : $collection,
            endpoint   : $endpoint, 
        ) ?? [];

        $options['context'] = [
            'provider'   => $provider,
            'collection' => $collection,
            'endpoint'   => $endpoint,
        ];

        return $options;
    }

    /**
     * Get the route schemes for a given provider, collection, and endpoint
     *
     * @param string $provider
     * @param string $collection
     * @param string $endpoint
     * @return array
     */
    public function getSchemes(string $provider, string $collection, string $endpoint): array
    {
        return $this->configuration->getRouteSchemes(
            provider   : $provider,
            collection : $collection,
            endpoint   : $endpoint, 
        ) ?? [];
    }

    /**
     * Get the route methods for a given provider, collection, and endpoint
     *
     * @param string $provider
     * @param string $collection
     * @param string $endpoint
     * @return array|null
     */
    public function getMethods(string $provider, string $collection, string $endpoint): array|null
    {
        return $this->configuration->getRouteMethods(
            provider   : $provider,
            collection : $collection,
            endpoint   : $endpoint,
        ) ?? null;
    }

    /**
     * Get the route condition for a given provider, collection, and endpoint
     *
     * @param string $provider
     * @param string $collection
     * @param string $endpoint
     * @return string|null
     */
    public function getCondition(string $provider, string $collection, string $endpoint): string|null
    {
        return $this->configuration->getRouteCondition(
            provider   : $provider,
            collection : $collection,
            endpoint   : $endpoint, 
        ) ?? null;
    }

    /**
     * Get the route defaults for a given provider, collection, and endpoint
     *
     * @param string $provider
     * @param string $collection
     * @param string $endpoint
     * @return array
     */
    public function getDefaults(string $provider, string $collection, string $endpoint): array
    {
        $controller = $this->configuration->getRouteController(
            provider   : $provider,
            collection : $collection,
            endpoint   : $endpoint, 
        ) ?? null;

        $defaults = [];
        $defaults['_controller'] = $controller;
        $defaults['_context'] = [
            'provider'   => $provider,
            'collection' => $collection,
            'endpoint'   => $endpoint,
        ];
        
        return $defaults;
    }

    /**
     * Add a route to the collection if it does not already exist
     *
     * @param array $route
     * @param array $options
     * @return void
     */
    public function addRouteToCollection(array &$route, array $options = []): void 
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
}