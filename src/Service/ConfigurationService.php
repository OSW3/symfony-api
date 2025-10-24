<?php 
namespace OSW3\Api\Service;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use OSW3\Api\DependencyInjection\Configuration;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigurationService
{
    private readonly array $configuration;
    private readonly ?Request $request;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly ManagerRegistry $doctrine,
        private readonly RequestStack $requestStack,
        #[Autowire(service: 'service_container')] private readonly ContainerInterface $container,
    ){
        $this->request = $requestStack->getCurrentRequest();
        $this->configuration = $container->getParameter(Configuration::NAME);
    }



    // ──────────────────────────────
    // CONTEXT
    // ──────────────────────────────

    /**
     * Get the current API context from the request and configuration.
     * 
     * @param string|null $part Specific part of the context to retrieve ('provider', 'collection', 'endpoint')
     * @return array|string|null Full context array or specific part value, or null if not found
     */
    public function getContext(?string $part = null): array|string|null
    {
        $route = $this->request->get('_route');

        if (!$route) {
            return null;
        }

        foreach ($this->getProviders() as $provider => $providerOptions) {
            foreach ($providerOptions['collections'] ?? [] as $collection => $entityOptions) {
                foreach ($entityOptions['endpoints'] ?? [] as $endpoint => $endpointOption) {
                    if (($endpointOption['route']['name'] ?? null) === $route) {
                        return match ($part) {
                            'provider'   => $provider,
                            'collection' => $collection,
                            'endpoint'   => $endpoint,
                            default      => compact('provider', 'collection', 'endpoint'),
                        };
                    }
                }
            }
        }

        return null;
    }



    // ──────────────────────────────
    // PROVIDERS
    // ──────────────────────────────

    /**
     * Returns the configuration for all API providers.
     * 
     * @return array An associative array of all providers. Each key is the provider name and each value is its configuration array.
     *
     * @example
     * $providers = $configurationService->getProviders();
     */
    public function getProviders(): array
    {
        return $this->configuration;
    }

    /**
     * Returns the configuration array for a specific API provider.
     *
     * @param string $providerName The name of the API provider (e.g., 'my_custom_api_v1').
     * @return array|null Returns the provider configuration array if found, or null if the provider does not exist.
     *
     * @example
     * $provider = $configurationService->getProvider('my_custom_api_v1'); 
     */
    public function getProvider(string $provider): ?array
    {
        $providers = $this->getProviders();
        return $providers[$provider] ?? null;
    }

    /**
     * Check if a specific API provider exists in the configuration.
     * 
     * @param string $provider Name of the API provider
     * @return bool True if the provider exists, false otherwise
     */
    public function hasProvider(string $provider): bool
    {
        $providers = $this->getProviders();
        return array_key_exists($provider, $providers);
    }
    
    /**
     * Check if a specific API provider is enabled.
     * 
     * @param string $provider Name of the API provider
     * @return bool True if the provider is enabled, false otherwise
     */
    public function isProviderEnabled(string $provider): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        $providerConfig = $this->getProvider($provider);
        return $providerConfig['enabled'] ?? false;
    }

    /**
     * Check if a specific API provider is deprecated.
     * 
     * @param string $provider Name of the API provider
     * @return bool True if the provider is deprecated, false otherwise
     */
    public function isProviderDeprecated(string $provider): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        $providerConfig = $this->getProvider($provider);
        return $providerConfig['deprecated'] ?? false;
    }



    // ──────────────────────────────
    // COLLECTIONS
    // ──────────────────────────────

    /**
     * Returns all collections defined for a specific provider.
     * dump( $this->configuration->getCollections('my_custom_api_v1') );
     *
     * @param string $providerName
     * @return array|null Returns an array of collections if the provider exists, null otherwise.
     */
    public function getCollections(string $provider): array
    {
        $providers = $this->getProviders();
        return $providers[$provider]['collections'] ?? [];
    }

    /**
     * Returns a specific collection configuration.
     * dump( $this->configuration->getCollection('my_custom_api_v1', 'App\Entity\Book') );
     *
     * @param string $providerName
     * @param string $entityClass Fully qualified class name of the entity (e.g., 'App\Entity\Book')
     * @return array|null Collection configuration array or null if not found.
     */
    public function getCollection(string $provider, string $collection): ?array
    {
        $collections = $this->getCollections($provider);
        return $collections[$collection] ?? null;
    }

    /**
     * Check if a specific collection exists for a given provider.
     * 
     * @param string $providerName Name of the API provider
     * @param string $entityClass Fully qualified class name of the entity (e.g., 'App\Entity\Book')
     * @return bool True if the collection exists, false otherwise
     */
    public function hasCollection(string $provider, string $collection): bool
    {
        $collections = $this->getCollections($provider);
        return array_key_exists($collection, $collections);
    }

    /**
     * Get the collection name for a specific collection within a provider.
     * dump( $this->configuration->getCollectionName('my_custom_api_v1', 'App\Entity\Book') );
     *
     * @param string $providerName
     * @param string $entityClass Fully qualified class name of the entity (e.g., 'App\Entity\Book')
     * @return string|null Collection name or null if not found.
     */
    public function getCollectionName(string $provider, string $collection): string|null
    {
        $collectionConfig = $this->getCollection($provider, $collection);
        return $collectionConfig['name'] ?? null;
    }

    /**
     * Check if a specific collection is enabled.
     * 
     * @param string $provider Name of the API provider
     * @param string $collection Name of the collection
     * @return bool True if the collection is enabled, false otherwise
     */
    public function isCollectionEnabled(string $provider, string $collection): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        if (! $this->hasCollection($provider, $collection)) {
            return false;
        }

        $collectionConfig = $this->getCollection($provider, $collection);
        return $collectionConfig['enabled'] ?? $this->isProviderEnabled($provider);
    }

    /**
     * Check if a specific collection is deprecated.
     * 
     * @param string $provider Name of the API provider
     * @param string $collection Name of the collection
     * @return bool True if the collection is deprecated, false otherwise
     */
    public function isCollectionDeprecated(string $provider, string $collection): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        if (! $this->hasCollection($provider, $collection)) {
            return false;
        }
        
        $collectionConfig = $this->getCollection($provider, $collection);
        return $collectionConfig['deprecated'] ?? false;
    }



    // ──────────────────────────────
    // ENDPOINTS
    // ──────────────────────────────

    /**
     * Returns all endpoints defined for a specific collection within a provider.
     * dump( $this->configuration->getEndpoints('my_custom_api_v1', 'App\Entity\Book') );
     *
     * @param string $providerName
     * @param string $entityClass Fully qualified class name of the entity (e.g., 'App\Entity\Book')
     * @return array|null Returns an array of endpoints if the collection exists, null otherwise.
     */
    public function getEndpoints(string $provider, string $collection): array
    {
        $collection = $this->getCollection($provider, $collection);
        return $collection['endpoints'] ?? [];
    }

    /**
     * Returns a specific endpoint configuration.
     * dump( $this->configuration->getEndpoint('my_custom_api_v1', 'App\Entity\Book', 'index') );
     *
     * @param string $providerName
     * @param string $entityClass Fully qualified class name of the entity (e.g., 'App\Entity\Book')
     * @param string $endpointName Name of the endpoint (e.g., 'index', 'show')
     * @return array|null Endpoint configuration array or null if not found.
     */
    public function getEndpoint(string $provider, string $collection, string $endpoint): ?array
    {
        $endpoints = $this->getEndpoints($provider, $collection);
        return $endpoints[$endpoint] ?? null;
    }

    /**
     * Check if a specific endpoint exists for a given collection within a provider.
     * 
     * @param string $providerName Name of the API provider
     * @param string $entityClass Fully qualified class name of the entity (e.g., 'App\Entity\Book')
     * @param string $endpointName Name of the endpoint (e.g., 'index', 'show')
     * @return bool True if the endpoint exists, false otherwise
     */
    public function hasEndpoint(string $provider, string $collection, string $endpoint): bool
    {
        $endpoints = $this->getEndpoints($provider, $collection);
        return array_key_exists($endpoint, $endpoints);
    }

    /**
     * Check if a specific endpoint is enabled.
     * 
     * @param string $provider Name of the API provider
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return bool True if the endpoint is enabled, false otherwise
     */
    public function isEndpointEnabled(string $provider, string $collection, string $endpoint): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        if (! $this->hasCollection($provider, $collection)) {
            return false;
        }
        
        if (! $this->hasEndpoint($provider, $collection, $endpoint)) {
            return false;
        }

        $endpointConfig = $this->getEndpoint($provider, $collection, $endpoint);
        return $endpointConfig['enabled'] ?? $this->isCollectionEnabled($provider, $collection);
    }

    /**
     * Check if a specific endpoint is deprecated.
     * 
     * @param string $provider Name of the API provider
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return bool True if the endpoint is deprecated, false otherwise
     */
    public function isEndpointDeprecated(string $provider, string $collection, string $endpoint): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        if (! $this->hasCollection($provider, $collection)) {
            return false;
        }
        
        if (! $this->hasEndpoint($provider, $collection, $endpoint)) {
            return false;
        }

        $endpointConfig = $this->getEndpoint($provider, $collection, $endpoint);
        return $endpointConfig['deprecated'] ?? false;
    }



    // ──────────────────────────────
    // VERSIONING
    // ──────────────────────────────

    /**
     * Get version information for a specific API provider.
     * 
     * @param string $provider Name of the API provider
     * @return array Version information array or empty array if provider not found
     */
    public function getVersion(string $provider): array
    {
        if (!$this->hasProvider($provider)) {
            return [];
        }

        $options = $this->getProvider($provider);
        return $options['version'];
    }

    /**
     * Get version number for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return int Version number
     */
    public function getVersionNumber(string $provider): int|null
    {
        if (!$this->hasProvider($provider)) {
            return null;
        }

        $options = $this->getProvider($provider);
        $version = $options['version'] ?? null;

        return $version['number'] ?? null;
    }

    /**
     * Get version prefix for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Version prefix (e.g., 'v')
     */
    public function getVersionPrefix(string $provider): string|null
    {
        if (!$this->hasProvider($provider)) {
            return null;
        }

        $options = $this->getProvider($provider);
        $version = $options['version'] ?? null;

        return $version['prefix'] ?? null;
    }

    /**
     * Get version location for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Version location (e.g., 'header', 'url')
     */
    public function getVersionLocation(string $provider): string|null
    {
        if (!$this->hasProvider($provider)) {
            return null;
        }

        $options = $this->getProvider($provider);
        $version = $options['version'] ?? null;

        return $version['location'] ?? null;
    }

    /**
     * Get version header format for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Version header format (e.g., 'application/vnd.{vendor}.v{version}+json')
     */
    public function getVersionHeaderFormat(string $provider): string|null
    {
        if (!$this->hasProvider($provider)) {
            return null;
        }

        $options = $this->getProvider($provider);
        $version = $options['version'] ?? null;

        return $version['header_format'] ?? null;
    }

    /**
     * Check if a specific API provider is marked as beta.
     * 
     * @param string $providerName Name of the API provider
     * @return bool True if the provider is beta, false otherwise
     */
    public function isVersionBeta(string $provider): bool
    {
        if (!$this->hasProvider($provider)) {
            return false;
        }

        $options = $this->getProvider($provider);
        $version = $options['version'] ?? null;

        return $version['beta'] ?? false;
    }

    /**
     * Check if a specific API provider is marked as deprecated.
     * 
     * @param string $providerName Name of the API provider
     * @return bool True if the provider is deprecated, false otherwise
     */
    public function isVersionDeprecated(string $provider): bool
    {
        if (!$this->hasProvider($provider)) {
            return false;
        }

        $options = $this->getProvider($provider);
        $version = $options['version'] ?? null;

        return $version['deprecated'] ?? false;
    }



    // ──────────────────────────────
    // ROUTE
    // global, collection, endpoint
    // ──────────────────────────────
    
    // Route configuration with fallback

    /**
     * Get the route configuration with optional fallback at endpoint, collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Route configuration array
     */
    public function getRoute(string $provider, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific route
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['route'])) {
                return $endpointOptions['route'];
            }
        }

        // 2. Collection-level route
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['route'])) {
                return $collectionOptions['route'];
            }
        }

        // 3. Global default route
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['routes'] ?? [];
    }

    /**
     * Get the route name pattern with optional fallback at endpoint, collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return string Route name pattern
     */
    public function getRouteNamePattern(string $provider, ?string $collection = null, ?string $endpoint = null): string
    {
        if (! $this->hasProvider($provider)) {
            return '';
        }

        // 1. Endpoint-specific pattern
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['route']['pattern'])) {
                return $endpointOptions['route']['pattern'];
            }
        }

        // 2. Collection-level pattern
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['route']['pattern'])) {
                return $collectionOptions['route']['pattern'];
            }
        }

        // 3. Global default pattern
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['routes']['pattern'] ?? '';
    }

    /**
     * Get the route prefix with optional fallback at endpoint, collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return string Route prefix
     */
    public function getRoutePrefix(string $provider, ?string $collection = null, ?string $endpoint = null): string
    {
        if (! $this->hasProvider($provider)) {
            return '';
        }

        // 1. Endpoint-specific prefix
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['route']['prefix'])) {
                return $endpointOptions['route']['prefix'];
            }
        }

        // 2. Collection-level prefix
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['route']['prefix'])) {
                return $collectionOptions['route']['prefix'];
            }
        }

        // 3. Global default prefix
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['routes']['prefix'] ?? '';
    }

    /**
     * Get the route hosts with optional fallback at endpoint, collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Route hosts
     */
    public function getRouteHosts(string $provider, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific hosts
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['route']['hosts'])) {
                return $endpointOptions['route']['hosts'];
            }
        }

        // 2. Collection-level hosts
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['route']['hosts'])) {
                return $collectionOptions['route']['hosts'];
            }
        }

        // 3. Global default hosts
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['routes']['hosts'] ?? [];
    }

    /**
     * Get the route schemes with optional fallback at endpoint, collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Route schemes
     */
    public function getRouteSchemes(string $provider, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific schemes
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['route']['schemes'])) {
                return $endpointOptions['route']['schemes'];
            }
        }

        // 2. Collection-level schemes
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['route']['schemes'])) {
                return $collectionOptions['route']['schemes'];
            }
        }

        // 3. Global default schemes
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['routes']['schemes'] ?? [];
    }

    // Route configuration for specific endpoint

    /**
     * Get the route name of a specific endpoint.
     * 
     * @param string $provider Name of the API provider
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return string|null Route name or null if not found
     */
    public function getRouteName(string $provider, string $collection, string $endpoint): string|null
    {
        $routeConfig = $this->getRoute($provider, $collection, $endpoint);
        return $routeConfig['name'] ?? null;
    }

    /**
     * Get the route methods of a specific endpoint.
     * 
     * @param string $provider Name of the API provider
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return array List of HTTP methods (GET, POST, etc.) or an empty array if not found
     */
    public function getRouteMethods(string $provider, string $collection, string $endpoint): array
    {
        $routeConfig = $this->getRoute($provider, $collection, $endpoint);
        return $routeConfig['methods'] ?? [];
    }

    /**
     * Get the route controller of a specific endpoint.
     *
     * @param string $provider Name of the API provider
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return string Route controller or an empty string if not found
     */
    public function getRouteController(string $provider, string $collection, string $endpoint): string
    {
        $routeConfig = $this->getRoute($provider, $collection, $endpoint);
        return $routeConfig['controller'] ?? '';
    }

    /**
     * Get the route options of a specific endpoint.
     *
     * @param string $provider Name of the API provider
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return array Route options or an empty array if not found
     */
    public function getRouteOptions(string $provider, string $collection, string $endpoint): array
    {
        $routeConfig = $this->getRoute($provider, $collection, $endpoint);
        return $routeConfig['options'] ?? [];
    }

    /**
     * Get the route requirements of a specific endpoint.
     *
     * @param string $provider Name of the API provider
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return array Route requirements or an empty array if not found
     */
    public function getRouteRequirements(string $provider, string $collection, string $endpoint): array
    {
        $routeConfig = $this->getRoute($provider, $collection, $endpoint);
        return $routeConfig['requirements'] ?? [];
    }

    /**
     * Get the route condition of a specific endpoint.
     *
     * @param string $provider Name of the API provider
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return string Route condition or an empty string if not found
     */
    public function getRouteCondition(string $provider, string $collection, string $endpoint): string
    {
        $routeConfig = $this->getRoute($provider, $collection, $endpoint);
        return $routeConfig['condition'] ?? '';
    }



    // ──────────────────────────────
    // PAGINATION
    // ──────────────────────────────

    /**
     * Get the pagination configuration with optional fallback at endpoint, collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Pagination configuration array
     */
    public function getPagination(string $provider, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific route
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['pagination'])) {
                return $endpointOptions['pagination'];
            }
        }

        // 2. Collection-level route
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['pagination'])) {
                return $collectionOptions['pagination'];
            }
        }

        // 3. Global default route
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['pagination'] ?? [];
    }

    /**
     * Check if pagination is enabled with optional fallback at endpoint, collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return bool True if pagination is enabled, false otherwise
     */
    public function isPaginationEnabled(string $provider, ?string $collection = null, ?string $endpoint = null): bool
    {
        if (! $this->hasProvider($provider)) {
            return true;
        }

        // 1. Endpoint-specific pagination enable
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['pagination']['enable'])) {
                return $endpointOptions['pagination']['enable'];
            }
        }

        // 2. Collection-level pagination enable
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['pagination']['enable'])) {
                return $collectionOptions['pagination']['enable'];
            }
        }

        // 3. Global default pagination enable
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['pagination']['enable'] ?? true;
    }

    /**
     * Get the number of items per page for pagination with optional fallback at endpoint, collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return int Number of items per page
     */
    public function getPaginationLimit(string $provider, ?string $collection = null, ?string $endpoint = null): int
    {
        if (! $this->hasProvider($provider)) {
            return 10;
        }

        // 1. Endpoint-specific pagination limit
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['pagination']['limit'])) {
                return $endpointOptions['pagination']['limit'];
            }
        }

        // 2. Collection-level pagination limit
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['pagination']['limit'])) {
                return $collectionOptions['pagination']['limit'];
            }
        }

        // 3. Global default pagination limit
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['pagination']['limit'] ?? 10;
    }

    /**
     * Get the maximum allowed limit for pagination with optional fallback at endpoint, collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return int Maximum allowed limit for pagination
     */
    public function getPaginationMaxLimit(string $provider, ?string $collection = null, ?string $endpoint = null): int
    {
        if (! $this->hasProvider($provider)) {
            return 100;
        }

        // 1. Endpoint-specific pagination max limit
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['pagination']['max_limit'])) {
                return $endpointOptions['pagination']['max_limit'];
            }
        }

        // 2. Collection-level pagination max limit
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['pagination']['max_limit'])) {
                return $collectionOptions['pagination']['max_limit'];
            }
        }

        // 3. Global default pagination max limit
        return $this->getProvider($provider)['pagination']['max_limit'] ?? 100;
    }

    /**
     * Check if overriding the pagination limit via request parameters is allowed,
     * with optional fallback at endpoint, collection or provider level.
     * e.g., ?limit=50
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return bool True if overriding is allowed, false otherwise
     */
    public function isPaginationLimitOverrideAllowed(string $provider, ?string $collection = null, ?string $endpoint = null): bool
    {
        if (! $this->hasProvider($provider)) {
            return true;
        }

        // 1. Endpoint-specific pagination allow_limit_override
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['pagination']['allow_limit_override'])) {
                return $endpointOptions['pagination']['allow_limit_override'];
            }
        }

        // 2. Collection-level pagination allow_limit_override
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['pagination']['allow_limit_override'])) {
                return $collectionOptions['pagination']['allow_limit_override'];
            }
        }

        // 3. Global default pagination allow_limit_override
        return $this->getProvider($provider)['pagination']['allow_limit_override'] ?? true;
    }



    // ──────────────────────────────
    // SEARCH 
    // ──────────────────────────────

    /**
     * Get the search configuration with optional fallback at collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @return array Search configuration array
     */
    public function getSearch(string $provider, ?string $collection = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Collection-level search enable
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['search'])) {
                return $collectionOptions['search'];
            }
        }

        // 2. Global default search enable
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['search'] ?? [];
    }

    /**
     * Check if search is enabled with optional fallback at collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @return bool True if search is enabled, false otherwise
     */
    public function isSearchEnabled(string $provider, ?string $collection = null): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        // 1. Collection-level search enable
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['search']['enabled'])) {
                return $collectionOptions['search']['enabled'];
            }
        }
        // 2. Global default search enable
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['search']['enabled'] ?? false;
    }

    /**
     * Get the searchable fields for a specific collection within a provider.
     * 
     * @param string $provider Name of the API provider
     * @param string $collection Name of the collection
     * @return array List of searchable fields
     */
    public function getSearchFields(string $provider, string $collection): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        if (! $this->hasCollection($provider, $collection)) {
            return [];
        }

        $collectionOptions = $this->getCollection($provider, $collection);
        return $collectionOptions['search']['fields'] ?? [];
    }



    // ──────────────────────────────
    // URL SUPPORT
    // ──────────────────────────────

    /**
     * Get the URL support configuration with optional fallback at collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @return array URL support configuration array
     */
    public function getUrlSupport(string $provider, ?string $collection = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['url'])) {
                return $collectionOptions['url'];
            }
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['url'] ?? [];
    }

    /**
     * Check if URL support is enabled with optional fallback at collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @return bool True if URL support is enabled, false otherwise
     */
    public function hasUrlSupport(string $provider, ?string $collection = null): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['url']['support'])) {
                return true;
            }
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['url']['support'] ?? false;
    }
    
    /**
     * Check if URLs are absolute with optional fallback at collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @return bool True if URLs are absolute, false otherwise
     */
    public function isUrlAbsolute(string $provider, ?string $collection = null): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['url']['absolute'])) {
                return $collectionOptions['url']['absolute'];
            }
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['url']['absolute'] ?? false;
    }

    /**
     * Get the URL property name with optional fallback at collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @return string URL property name
     */
    public function getUrlProperty(string $provider, ?string $collection = null): string
    {
        if (! $this->hasProvider($provider)) {
            return '';
        }

        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['url']['property'])) {
                return $collectionOptions['url']['property'];
            }
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['url']['property'] ?? '';
    }



    // ──────────────────────────────
    // RATE LIMITING
    // ──────────────────────────────

    /**
     * Get the rate limit configuration with optional fallback at endpoint, collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Rate limit configuration array
     */
    public function getRateLimit(string $provider, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific rate limit
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['rate_limit'])) {
                return $endpointOptions['rate_limit'];
            }
        }

        // 2. Collection-level rate limit
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['rate_limit'])) {
                return $collectionOptions['rate_limit'];
            }
        }

        // 3. Global default rate limit
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['rate_limit'] ?? [];
    }

    /**
     * Check if rate limiting is enabled with optional fallback at endpoint, collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return bool True if rate limiting is enabled, false otherwise
     */
    public function isRateLimitEnabled(string $provider, ?string $collection = null, ?string $endpoint = null): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        // 1. Endpoint-specific rate limit enable
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['rate_limit']['enabled'])) {
                return $endpointOptions['rate_limit']['enabled'];
            }
        }

        // 2. Collection-level rate limit enable
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['rate_limit']['enabled'])) {
                return $collectionOptions['rate_limit']['enabled'];
            }
        }

        // 3. Global default rate limit enable
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['rate_limit']['enabled'] ?? false;
    }

    /**
     * Get the rate limit value with optional fallback at endpoint, collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return string Rate limit value (e.g., '100/hour')
     */
    public function getRateLimitValue(string $provider, ?string $collection = null, ?string $endpoint = null): string
    {
        if (! $this->hasProvider($provider)) {
            return '100/hour';
        }

        // 1. Endpoint-specific rate limit
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['rate_limit']['limit'])) {
                return $endpointOptions['rate_limit']['limit'];
            }
        }

        // 2. Collection-level rate limit
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['rate_limit']['limit'])) {
                return $collectionOptions['rate_limit']['limit'];
            }
        }

        // 3. Global default rate limit
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['rate_limit']['limit'] ?? '100/hour';
    }

    /**
     * Get the rate limit configuration by role with optional fallback at endpoint, collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Rate limit configuration by role
     */
    public function getRateLimitByRole(string $provider, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific rate limit by_role
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['rate_limit']['by_role'])) {
                return $endpointOptions['rate_limit']['by_role'];
            }
        }

        // 2. Collection-level rate limit by_role
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['rate_limit']['by_role'])) {
                return $collectionOptions['rate_limit']['by_role'];
            }
        }

        // 3. Global default rate limit by_role
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['rate_limit']['by_role'] ?? [];
    }

    /**
     * Get the rate limit configuration by role with optional fallback at endpoint, collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Rate limit configuration by role
     */
    public function getRateLimitByUser(string $provider, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific rate limit by_user
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['rate_limit']['by_user'])) {
                return $endpointOptions['rate_limit']['by_user'];
            }
        }

        // 2. Collection-level rate limit by_user
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['rate_limit']['by_user'])) {
                return $collectionOptions['rate_limit']['by_user'];
            }
        }

        // 3. Global default rate limit by_user
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['rate_limit']['by_user'] ?? [];
    }

    /**
     * Get the rate limit configuration by user with optional fallback at endpoint, collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Rate limit configuration by user
     */
    public function getRateLimitByIp(string $provider, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific rate limit by_ip
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['rate_limit']['by_ip'])) {
                return $endpointOptions['rate_limit']['by_ip'];
            }
        }

        // 2. Collection-level rate limit by_ip
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['rate_limit']['by_ip'])) {
                return $collectionOptions['rate_limit']['by_ip'];
            }
        }

        // 3. Global default rate limit by_ip
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['rate_limit']['by_ip'] ?? [];
    }

    /**
     * Get the rate limit configuration by application with optional fallback at endpoint, collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Rate limit configuration by application
     */
    public function getRateLimitByApp(string $provider, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific rate limit by_application
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['rate_limit']['by_application'])) {
                return $endpointOptions['rate_limit']['by_application'];
            }
        }

        // 2. Collection-level rate limit by_application
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['rate_limit']['by_application'])) {
                return $collectionOptions['rate_limit']['by_application'];
            }
        }

        // 3. Global default rate limit by_application
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['rate_limit']['by_application'] ?? [];
    }



    // ──────────────────────────────
    // TEMPLATES
    // ──────────────────────────────

    public function getTemplates(string $provider, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific templates
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['templates'])) {
                return $endpointOptions['templates'];
            }
        }

        // 2. Collection-level templates
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $collection);
            if ($collectionOptions && isset($collectionOptions['templates'])) {
                return $collectionOptions['templates'];
            }
        }

        // 3. Global default templates
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['templates'] ?? [];
    }

    

    /**
     * Get the template path for list responses for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Path to the list response template
     */
    public function getListTemplate(string $providerName): string
    {
        return $this->configuration[$providerName]['response']['templates']['list'] ?? 'Resources/templates/list.yaml';
    }

    /**
     * Get the template path for item responses for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Path to the item response template
     */
    public function getItemTemplate(string $providerName): string
    {
        return $this->configuration[$providerName]['response']['templates']['item'] ?? 'Resources/templates/item.yaml';
    }

    /**
     * Get the template path for error responses for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Path to the error response template
     */
    public function getErrorTemplate(string $providerName): string
    {
        return $this->configuration[$providerName]['response']['templates']['error'] ?? 'Resources/templates/error.yaml';
    }

    /**
     * Get the template path for no content (204) responses for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Path to the no content response template
     */
    public function getNoContentTemplate(string $providerName): string
    {
        return $this->configuration[$providerName]['response']['templates']['no_content'] ?? 'Resources/templates/no_content.yaml';
    }

    /**
     * Get the response format for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Response format (e.g., 'json', 'xml')
     */
    public function getResponseFormat(string $providerName): string
    {
        return $this->configuration[$providerName]['response']['format'] ?? 'json';
    }











    
    // ──────────────────────────────
    // DEBUG & TRACING
    // ──────────────────────────────

    public function isDebugEnabled(string $providerName): bool
    {
        return $this->configuration[$providerName]['debug']['enable'] ?? false;
    }

    public function isTracingEnabled(string $providerName): bool
    {
        return $this->configuration[$providerName]['tracing']['enable'] ?? false;
    }

    public function isTracingIdRequestEnabled(string $providerName): bool
    {
        return $this->configuration[$providerName]['tracing']['request'] ?? false;
    }



    // ──────────────────────────────
    // RESPONSES
    // ──────────────────────────────

    // Headers

    /**
     * Get custom HTTP headers to include in all API responses for a specific provider.
     *
     * @param string $providerName Name of the API provider
     * @return array Associative array of HTTP headers (e.g., ['X-Custom-Header' => 'Value'])
     */
    public function getResponseHeaders(string $providerName): array
    {
        return $this->configuration[$providerName]['response']['headers'] ?? [];
    }

    // Response cache control

    /**
     * Construct the Cache-Control header value based on configuration settings.
     *
     * @param string $providerName Name of the API provider
     * @return string Constructed Cache-Control header value
     *
     * @example
     * $cacheControl = $configService->getResponseCacheControl('my_custom_api_v1');
     * // Possible output: "public, max-age=3600, must-revalidate"
     */
    public function getResponseCacheControl(string $providerName): string
    {
        $isPublic       = $this->isResponseCachePublic($providerName);
        $noStore        = $this->getResponseCacheControlNoStore($providerName);
        $mustRevalidate = $this->getResponseCacheControlMustRevalidate($providerName);
        $maxAge         = $this->getResponseCacheControlMaxAge($providerName);

        $directives     = [];
        $directives[]   = $isPublic ? 'public' : 'private';
        $directives[]   = $noStore ? 'no-store' : null;
        $directives[]   = $mustRevalidate ? 'must-revalidate' : '';
        $directives[]   = $maxAge ? 'max-age=' . (int) $maxAge : '';

        $directives = array_filter($directives, static fn($v) => !empty(trim($v)));

        return implode(', ', $directives);
    }

    /**
     * Get the hashing algorithm used for generating response hashes.
     *
     * @param string $providerName Name of the API provider
     * @return string Hashing algorithm (e.g., 'md5', 'sha256')
     */
    public function isResponseCachePublic(string $providerName): bool
    {
        return $this->configuration[$providerName]['response']['cache_control']['public'] ?? false;
    }

    /**
     * Determine if the 'no-store' directive should be included in the Cache-Control header.
     *
     * @param string $providerName Name of the API provider
     * @return bool True if 'no-store' should be included, false otherwise
     *
     * @example
     * $noStore = $configService->getResponseCacheControlNoStore('my_custom_api_v1'); // returns true or false
     */
    public function getResponseCacheControlNoStore(string $providerName): bool
    {
        return $this->configuration[$providerName]['response']['cache_control']['no_store'] ?? false;
    }

    /**
     * Determine if the 'must-revalidate' directive should be included in the Cache-Control header.
     *
     * @param string $providerName Name of the API provider
     * @return bool True if 'must-revalidate' should be included, false otherwise
     *
     * @example
     * $mustRevalidate = $configService->getResponseCacheControlMustRevalidate('my_custom_api_v1'); // returns true or false
     */
    public function getResponseCacheControlMustRevalidate(string $providerName): bool
    {
        return $this->configuration[$providerName]['response']['cache_control']['must_revalidate'] ?? false;
    }

    /**
     * Get the maximum age (in seconds) for the Cache-Control header.
     *
     * @param string $providerName Name of the API provider
     * @return int Maximum age in seconds
     *
     * @example
     * $maxAge = $configService->getResponseCacheControlMaxAge('my_custom_api_v1'); // returns 3600
     */
    public function getResponseCacheControlMaxAge(string $providerName): int
    {
        return $this->configuration[$providerName]['response']['cache_control']['max_age'] ?? 0;
    }

    // Hashing

    /**
     * Get the hashing algorithm used for generating response hashes.
     *
     * @param string $providerName Name of the API provider
     * @return string Hashing algorithm (e.g., 'md5', 'sha256')
     *
     * @example
     * $algorithm = $configService->getResponseHashAlgorithm('my_custom_api_v1'); // returns 'md5'
     */
    public function getResponseHashingAlgorithm(string $providerName): string
    {
        return $this->configuration[$providerName]['response']['algorithm'] ?? 'md5';
    }

    // Compression / GZIP

    public function isCompressionEnabled(string $providerName): bool
    {
        return $this->configuration[$providerName]['response']['compression']['enable'] ?? false;
    }
    public function getCompressionLevel(string $providerName): int
    {
        return $this->configuration[$providerName]['response']['compression']['level'] ?? 6;
    }
    public function getCompressionFormat(string $providerName): string
    {
        return $this->configuration[$providerName]['response']['compression']['format'] ?? 'gzip';
    }



    // ──────────────────────────────
    // SECURITY
    // ──────────────────────────────

    /**
     * Get the fully qualified class name of the security entity for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Fully qualified class name of the security entity
     */
    public function getSecurityEntityClass(string $providerName): string
    {
        return $this->configuration[$providerName]['security']['entity']['class'] ?? '';
    }

    /**
     * Get the name of the security collection for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Name of the security collection
     */
    public function getSecurityCollectionName(string $providerName): string
    {
        return $this->configuration[$providerName]['security']['routes']['collection'] ?? '';
    }

    // Registration

    /**
     * Check if user registration is enabled for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return bool True if registration is enabled, false otherwise
     */
    public function isRegistrationEnabled(string $providerName): bool
    {
        return $this->configuration[$providerName]['security']['register']['enable'] ?? false;
    }

    /**
     * Get the HTTP method used for user registration for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string HTTP method (e.g., 'POST', 'PUT')
     */
    public function getRegistrationMethod(string $providerName): string
    {
        return $this->configuration[$providerName]['security']['register']['method'] ?? 'POST';
    }

    /**
     * Get the URL path for user registration for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string|null URL path for registration, or null if not defined
     */
    public function getRegistrationPath(string $providerName): ?string
    {
        return $this->configuration[$providerName]['security']['register']['path'] ?? null;
    }

    /**
     * Get the controller responsible for handling user registration for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Fully qualified class name of the registration controller
     */
    public function getRegistrationController(string $providerName): string
    {
        return $this->configuration[$providerName]['security']['register']['controller'] ?? 'OSW3\Api\Controller\RegisterController::register';
    }

    /**
     * Get the properties required for user registration for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return array Array of registration properties
     */
    public function getRegistrationProperties(string $providerName): array
    {
        return $this->configuration[$providerName]['security']['register']['properties'] ?? [];
    }

    // Login

    /**
     * Check if user login is enabled for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return bool True if login is enabled, false otherwise
     */
    public function isLoginEnabled(string $providerName): bool
    {
        return $this->configuration[$providerName]['security']['login']['enable'] ?? false;
    }

    /**
     * Get the HTTP method used for user login for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string HTTP method (e.g., 'POST', 'PUT')
     */
    public function getLoginMethod(string $providerName): string
    {
        return $this->configuration[$providerName]['security']['login']['method'] ?? 'POST';
    }

    /**
     * Get the URL path for user login for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string|null URL path for login, or null if not defined
     */
    public function getLoginPath(string $providerName): ?string
    {
        return $this->configuration[$providerName]['security']['login']['path'] ?? null;
    }

    /**
     * Get the controller responsible for handling user login for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Fully qualified class name of the login controller
     */
    public function getLoginController(string $providerName): ?string
    {
        return $this->configuration[$providerName]['security']['login']['controller'] ?? 'OSW3\Api\Controller\SecurityController::login';
    }

    /**
     * Get the properties required for user login for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return array Array of login properties
     */
    public function getLoginProperties(string $providerName): array
    {
        return $this->configuration[$providerName]['security']['login']['properties'] ?? [];
    }



    // ──────────────────────────────
    // Serialization
    // ──────────────────────────────

    public function getSerializerGroups(string $providerName, string $entityClass, string $endpointName): array
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['serialization']['groups'] ?? [];
    }

    public function getSerializerIgnore(string $providerName, string $entityClass, string $endpointName): array
    {
        return array_merge(
            $this->configuration[$providerName]['serialization']['ignore'],
            $this->getEndpoint($providerName, $entityClass, $endpointName)['serialization']['ignore']
        );
    }

    public function getSerializerDatetimeFormat(string $providerName): string
    {
        return $this->configuration[$providerName]['serialization']['datetime']['format'] ?? 'Y-m-d H:i:s';
    }

    /**
     * Get the timezone used for serializing datetime fields in API responses.
     * 
     * @param string $providerName Name of the API provider
     * @return string Timezone identifier (e.g., 'UTC', 'America/New_York')
     */
    public function getSerializerTimezone(string $providerName): string
    {
        return $this->configuration[$providerName]['serialization']['datetime']['timezone'] ?? 'UTC';
    }

    /**
     * Determine if null values should be skipped during serialization in API responses.
     * 
     * @param string $providerName Name of the API provider
     * @return bool True if null values should be skipped, false otherwise
     */
    public function getSerializerSkipNull(string $providerName): bool
    {
        return $this->configuration[$providerName]['serialization']['skip_null'] ?? false;
    }

    /**
     * Get the custom transformer service used for serializing entities in API responses.
     * 
     * @param string $providerName Name of the API provider
     * @param string $entityClass Fully qualified class name of the entity (e.g., 'App\Entity\Book')
     * @param string $endpointName Name of the specific endpoint (e.g., 'list', 'detail')
     * @return string Fully qualified class name of the transformer service, or an empty string if not defined
     */
    public function getSerializerTransformer(string $providerName, string $entityClass, string $endpointName): string
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['serialization']['transformer'] ?? '';
    }

    


    // ──────────────────────────────
    // Endpoints Repository
    // ──────────────────────────────

    public function getRepositoryClass(string $providerName, string $entityClass, string $endpointName): ?string
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['repository']['service'] ?? '';
    }

    // TODO: remove this method
    public function getRepository(string $providerName, string $entityClass, string $endpointName)
    {
        $repositoryClass = $this->getRepositoryClass($providerName, $entityClass, $endpointName);

        if (!empty($repositoryClass)) {
            foreach ($this->doctrine->getManager()->getMetadataFactory()->getAllMetadata() as $meta) {
                if ($meta->customRepositoryClassName === $repositoryClass) {
                    return $this->doctrine->getRepository($meta->getName());
                }
            }
        }

        return $this->getEntityRepository($entityClass);
    }

    public function getMethod(string $providerName, string $entityClass, string $endpointName): ?string
    {
        $repositoryMethod = $this->getEndpoint($providerName, $entityClass, $endpointName)['repository']['method'] ?? null;
        return $repositoryMethod;

        // if (!empty($repositoryMethod)) {
        //     return $repositoryMethod;
        // }

        // $requestMethod = $this->request->getMethod();
        // $id            = $this->request->get('id');
        // // $criteria      = $this->getCriteria($providerName, $entityClass, $endpointName);
        // // $orderBy       = $this->getOrderBy($providerName, $entityClass, $endpointName);
        // // $limit         = $this->getLimit($providerName, $entityClass, $endpointName);

        // return match ($requestMethod) {
        //     // Request::METHOD_GET    => $id ? "find" : "findAll",
        //     Request::METHOD_GET    => $id ? "find" : "findBy",
        //     Request::METHOD_PUT    => "update",
        //     Request::METHOD_POST   => "add",
        //     Request::METHOD_PATCH  => "update",
        //     Request::METHOD_DELETE => "delete",
        //     default => null
        // };

        // // // Choix intelligent de la méthode par défaut
        // // if (!$repositoryMethod) {
        // //     $repositoryMethod = match ($request->getMethod()) {
        // //         Request::METHOD_GET    => $id ? 'find' : 'findBy',
        // //         Request::METHOD_PUT    => 'update',
        // //         Request::METHOD_POST   => 'create',
        // //         Request::METHOD_PATCH  => 'update',
        // //         Request::METHOD_DELETE => 'delete',
        // //         default => null
        // //     };
        // // }

        // // // Construction automatique des arguments selon la méthode
        // // $args = match ($repositoryMethod) {
        // //     'find'   => [$id],
        // //     'findBy' => [$criteria, $orderBy ?: null, $limit ?: null, $offset ?: null],
        // //     default  => $id ? [$id] : []
        // // };

        // // return [
        // //     'method' => $repositoryMethod,
        // //     'args'   => array_filter($args, fn($v) => $v !== null) // nettoie les null inutiles
        // // ];
    }

    public function getCriteria(string $providerName, string $entityClass, string $endpointName): array
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['repository']['criteria'] ?? [];
    }

    public function getOrderBy(string $providerName, string $entityClass, string $endpointName): array
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['repository']['order_by'] ?? [];
    }

    public function getLimit(string $providerName, string $entityClass, string $endpointName): ?int
    {
        $limit = $this->getEndpoint($providerName, $entityClass, $endpointName)['repository']['limit'];

        if (!empty($limit)) {
            return $limit;
        }

        if ($this->isPaginationEnabled($providerName)) {
            return $this->getPaginationLimit($providerName, $entityClass);
        }

        return null;
    }

    public function getFetchMode(string $providerName, string $entityClass, string $endpointName): string
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['repository']['fetch_mode'] ?? '';
    }


    // ──────────────────────────────
    // Endpoints Metadata
    // ──────────────────────────────

    public function getMetadata(string $providerName, string $entityClass, string $endpointName): ?array
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['metadata'] ?? null;
    }

    public function getMetadataDescription(string $providerName, string $entityClass, string $endpointName): ?string
    {
        return $this->getMetadata($providerName, $entityClass, $endpointName)['description'] ?? null;
    }

    public function getMetadataSummary(string $providerName, string $entityClass, string $endpointName): ?string
    {
        return $this->getMetadata($providerName, $entityClass, $endpointName)['summary'] ?? null;
    }

    public function getMetadataDeprecated(string $providerName, string $entityClass, string $endpointName): bool
    {
        return $this->getMetadata($providerName, $entityClass, $endpointName)['deprecated'] ?? false;
    }

    public function getMetadataCacheTTL(string $providerName, string $entityClass, string $endpointName): ?int
    {
        return $this->getMetadata($providerName, $entityClass, $endpointName)['cache_ttl'] ?? null;
    }

    public function getMetadataTags(string $providerName, string $entityClass, string $endpointName): ?string
    {
        return $this->getMetadata($providerName, $entityClass, $endpointName)['tags'] ?? null;
    }

    public function getMetadataOperationId(string $providerName, string $entityClass, string $endpointName): ?string
    {
        return $this->getMetadata($providerName, $entityClass, $endpointName)['operation_id'] ?? null;
    }


    // ──────────────────────────────
    // Endpoints Granted
    // ──────────────────────────────

    public function getRoles(string $providerName, string $entityClass, string $endpointName): array
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['granted']['roles'];
    }

    public function getVoter(string $providerName, string $entityClass, string $endpointName): string
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['granted']['voter'] ?? '';
    }


    // ──────────────────────────────
    // Endpoints Hooks
    // ──────────────────────────────

    public function getHooks(string $providerName, string $entityClass, string $endpointName): ?array
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['hooks'] ?? ['before' => [], 'after' => []];
    }


    // ──────────────────────────────
    // Endpoints Transformer
    // ──────────────────────────────

    public function getTransformer(string $providerName, string $entityClass, string $endpointName): string
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['transformer'] ?? '';
    }




    // ──────────────────────────────
    // DOCUMENTATION
    // ──────────────────────────────

    /**
     * Check if API documentation is enabled for a specific provider.
     *
     * @param string $providerName Name of the API provider
     * @return bool True if documentation is enabled, false otherwise
     *
     * @example
     * $enabled = $configService->isDocumentationEnabled('my_custom_api_v1'); // returns true or false
     */
    public function isDocumentationEnabled(string $providerName): bool
    {
        return $this->configuration[$providerName]['documentation']['enable'] ?? false;
    }


    // ──────────────────────────────
    // Utils
    // ──────────────────────────────


    public function getEntityRepository(string $entity)
    {
        return $this->doctrine->getRepository($entity);
    }

}