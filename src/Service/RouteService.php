<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\EndpointService;
use OSW3\Api\Service\ProviderService;
use OSW3\Api\Service\CollectionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RouteService 
{
    private readonly ?Request $request;
    
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ContextService $contextService,
        private readonly EndpointService $endpointService,
        private readonly ProviderService $providerService,
        private readonly CollectionService $collectionService,
    ){
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * Get the route options for a provider, segment, collection or endpoint
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return array
     */
    private function options(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->providerService->exists($provider)) {
            return [];
        }

        // 1. Endpoint-specific route
        if ($collection && $endpoint) {
            $endpointOptions = $this->endpointService->get($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['route'])) {
                return $endpointOptions['route'];
            }
        }

        // 2. Collection-level route
        if ($collection) {
            $collectionOptions = $this->collectionService->get($provider, $segment, $collection);
            if ($collectionOptions && isset($collectionOptions['route'])) {
                return $collectionOptions['route'];
            }
        }

        // 3. Global default route
        $providerOptions = $this->providerService->get($provider);
        return $providerOptions['routes'] ?? [];
    }


    // -- CONFIG OPTIONS GETTERS

    /**
     * Get the route pattern for a given provider, collection, and endpoint
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return string|null
     */
    public function getPattern(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): ?string
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        return $this->options(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint,
        )['pattern'] ?? null;
    }

    /**
     * Get the route prefix for a given provider, collection, and endpoint
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return string|null
     */
    public function getPrefix(?string $provider = null, ?string $segment = null, ?string $collection = null, bool $fallbackOnCurrentContext = true): ?string
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
        }

        return $this->options(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
        )['prefix'] ?? null;
    }

    /**
     * Get the route name for a given provider, collection, and endpoint
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return string|null
     */
    public function getName(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): ?string
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        $name = $this->options(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint,
        )['name'] ?? null;

        return preg_replace('/-/', '_', $name);
    }

    /**
     * Get the route requirements for a given provider, collection, and endpoint
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return array
     */
    public function getPath(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): ?string
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        $path =$this->options(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint,
        )['path'] ?? '';

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
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return array
     */
    public function getRequirements(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): array
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        return $this->options(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint,
        )['requirements'] ?? [];
    }

    /**
     * Get the route options for a given provider, collection, and endpoint
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return array
     */
    public function getOptions(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): array
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        $options = $this->options(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint,
        )['options'] ?? [];

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
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return array
     */
    public function getHosts(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): array
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        return $this->options(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint,
        )['options'] ?? [];
    }

    public function getHost(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): ?string
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

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
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return array
     */
    public function getSchemes(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): array
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        return $this->options(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint,
        )['schemes'] ?? [];
    }

    /**
     * Get the route methods for a given provider, collection, and endpoint
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return array|null
     */
    public function getMethods(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): ?array
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        return $this->options(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint,
        )['methods'] ?? null;
    }

    /**
     * Get the route condition for a given provider, collection, and endpoint
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return string
     */
    public function getCondition(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): string
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        return $this->options(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint,
        )['condition'] ?? '';
    }


    // -- COMPUTED GETTERS

    /**
     * Get the route defaults for a given provider, collection, and endpoint
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return array
     */
    public function getDefaults(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): array
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        $controller = $this->options(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
            endpoint   : $endpoint, 
        )['controller'] ?? '';

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
     * Get all exposed routes from all providers, segments, collections, and endpoints
     * 
     * @return array
     */
    public function getExposedRoutes(): array
    {
        // Collect all exposed routes from configuration
        $routes = [];

        // Get all providers
        $providers = $this->providerService->all();

        foreach ($providers as $provider => $options) {

            // Continue if provider is not enabled
            if (!$this->providerService->isEnabled($provider)) continue;

            // Merge authentication and collection routes
            $c1 = $this->collectionService->all($provider, ContextService::SEGMENT_AUTHENTICATION);
            $c2 = $this->collectionService->all($provider, ContextService::SEGMENT_COLLECTION);
            $collections = array_merge($c1, $c2);

            // Iterate over each collection
            foreach ($collections as $collection => $options) {
                $endpoints = $options['endpoints'] ?? [];
                $segment   = in_array($collection, array_keys($c1), true)
                            ? ContextService::SEGMENT_AUTHENTICATION
                            : ContextService::SEGMENT_COLLECTION;

                // Iterate over each endpoint
                foreach ($endpoints as $endpoint => $options) {

                    if (!$this->endpointService->isEnabled(
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
     * Get the current route information based on the request
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
     * Check if a route is registered in the exposed routes
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
     * @param array &$route
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
}