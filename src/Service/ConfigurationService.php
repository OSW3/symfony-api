<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ContextService;
use Symfony\Component\HttpFoundation\Request;
use OSW3\Api\DependencyInjection\Configuration;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ConfigurationService
{
    private readonly array $configuration;
    private readonly ?Request $request;

    public function __construct(
        #[Autowire(service: 'service_container')] 
        private readonly ContainerInterface $container,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
    ){
        $this->request = $requestStack->getCurrentRequest();
        $this->configuration = $container->getParameter(Configuration::NAME);
    }


    // ──────────────────────────────
    // CONTEXT
    // ──────────────────────────────

    /**
     * Get the current API context from the request and configuration.
     * e.g.: $this->configuration->getContext('provider');
     * 
     * @param string|null $part Specific part of the context to retrieve ('provider', 'collection', 'endpoint')
     * @return array|string|null Full context array or specific part value, or null if not found
     */
    public function getContext(?string $part = null): array|string|null
    {
        if (! $this->request || ! $this->request->attributes->get('_route')) {
            return null;
        }

        $context = $this->request->attributes->get('_context', []);

        return match ($part) {
            'provider'   => $context['provider'] ?? null,
            'segment'    => $context['segment'] ?? null,
            'collection' => $context['collection'] ?? null,
            'endpoint'   => $context['endpoint'] ?? null,
            default      => $context,
        };
    }

    public function isEnabled(?string $provider, ?string $segment = null, ?string $collection = null, ?string $endpoint = null): bool
    {
        
        if (! $this->hasProvider($provider)) {
            return false;
        }

        // 1. Endpoint-level
        if (
            $endpoint !== null &&
            $this->hasEndpoint($provider, $segment, $collection, $endpoint) &&
            $this->isEndpointEnabled($provider, $segment, $collection, $endpoint)
        ) {
            return true;
        }

        // 2. Collection-level
        if (
            $collection !== null &&
            $this->hasCollection($provider, $segment, $collection) &&
            $this->isCollectionEnabled($provider, $segment, $collection)
        ) {
            return true;
        }

        // 3. Provider/Segment-level
        if ($this->isProviderEnabled($provider, $segment)) {
            return true;
        }

        // 4. Default
        return false;
    }


    // ──────────────────────────────
    // PROVIDERS
    // ──────────────────────────────

    /**
     * Returns the configuration for all API providers.
     * e.g.: $this->configuration->getProviders();
     * 
     * @return array Associative array of all API providers and their configurations
     */
    public function getProviders(): array
    {
        return $this->configuration;
    }

    public function getProviderNames(): array
    {
        return array_keys($this->getProviders());
    }

    /**
     * Returns the configuration array for a specific API provider.
     * e.g.: $this->configuration->getProvider('my_custom_api_v1');
     * 
     * @param string $provider Name of the API provider
     * @return array|null Configuration array for the specified provider, or null if not found
     */
    public function getProvider(?string $provider): ?array
    {
        return $this->getProviders()[$provider] ?? null;
    }

    /**
     * Check if a specific API provider exists in the configuration.
     * e.g.: $this->configuration->hasProvider('my_custom_api_v1');
     * 
     * @param string $provider Name of the API provider
     * @return bool True if the provider exists, false otherwise
     */
    public function hasProvider(?string $provider): bool
    {
        return array_key_exists($provider, $this->getProviders());
    }
    
    /**
     * Check if a specific API provider is enabled.
     * e.g.: $this->configuration->isProviderEnabled('my_custom_api_v1');
     * 
     * @param string $provider Name of the API provider
     * @return bool True if the provider is enabled, false otherwise
     */
    public function isProviderEnabled(?string $provider): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        return $this->getProvider($provider)['enabled'] ?? false;
    }


    // ──────────────────────────────
    // SEGMENT
    // ──────────────────────────────

    public function isSegment(?string $segment): bool
    {
        return in_array($segment, [
            ContextService::SEGMENT_COLLECTION, 
            ContextService::SEGMENT_AUTHENTICATION
        ]);
    }

    public function hasSegment(?string $provider, ?string $segment): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        return array_key_exists($segment, $this->getProvider($provider));
    }


    // ──────────────────────────────
    // COLLECTIONS
    // ──────────────────────────────

    /**
     * Returns all collections defined for a specific provider.
     * e.g. $this->configuration->getCollections('my_custom_api_v1', 'collections');
     *
     * @param string $providerName
     * @param string $segment Type of collections to retrieve ('collections' or 'authentication')
     * @return array|null Returns an array of collections if the provider exists, null otherwise.
     */
    public function getCollections(?string $provider, ?string $segment): array
    {
        if (
            ! $this->hasProvider($provider) || 
            ! $this->isProviderEnabled($provider) ||
            ! $this->hasSegment($provider, $segment)
        ) return [];

        return $this->getProviders()[$provider][$segment] ?? [];
    }

    /**
     * Returns a specific collection configuration.
     * e.g. $this->configuration->getCollection('my_custom_api_v1', 'collections', 'App\Entity\Book');
     *
     * @param string $provider Name of the API provider
     * @param string $segment Type of collections to retrieve ('collections' or 'authentication')
     * @param string $collection Fully qualified class name of the entity (e.g., 'App\Entity\Book')
     * @return array|null Collection configuration array or null if not found.
     */
    public function getCollection(?string $provider, ?string $segment, ?string $collection): array
    {
        if (
            ! $this->hasProvider($provider) ||
            ! $this->isProviderEnabled($provider)
        ) return [];

        return $this->getCollections($provider, $segment)[$collection] ?? [];
    }
    
    /**
     * Check if a specific collection exists for a given provider.
     * e.g. $this->configuration->hasCollection('my_custom_api_v1', 'collections', 'App\Entity\Book');
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Type of collections to retrieve ('collections' or 'authentication')
     * @param string $collection Fully qualified class name of the entity (e.g., 'App\Entity\Book')
     * @return bool True if the collection exists, false otherwise
     */
    public function hasCollection(?string $provider, ?string $segment, ?string $collection): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        return array_key_exists($collection, $this->getCollections($provider, $segment));
    }

    /**
     * Get the collection name for a specific collection within a provider.
     * e.g.: $this->configuration->getCollectionName('my_custom_api_v1', 'collections', 'App\Entity\Book');
     *
     * @param string $provider Name of the API provider
     * @param string $segment Type of collections to retrieve ('collections' or 'authentication')
     * @param string $collection Fully qualified class name of the entity (e.g., 'App\Entity\Book')
     * @return string|null Collection name or null if not found.
     */
    public function getCollectionName(?string $provider, ?string $segment, ?string $collection): string|null
    {
        return $this->getCollection($provider, $segment, $collection)['name'] ?? null;
    }

    /**
     * Check if a specific collection is enabled.
     * e.g.: $this->configuration->isCollectionEnabled('my_custom_api_v1', 'collections', 'App\Entity\Book');
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Type of collections to retrieve ('collections' or 'authentication')
     * @param string $collection Fully qualified class name of the entity (e.g., 'App\Entity\Book')
     * @return bool True if the collection is enabled, false otherwise
     */
    public function isCollectionEnabled(?string $provider, ?string $segment, ?string $collection): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        if (! $this->hasCollection($provider, $segment, $collection)) {
            return false;
        }

        return $this->getCollection($provider, $segment, $collection)['enabled'] ?? $this->isProviderEnabled($provider);
    }


    // ──────────────────────────────
    // ENDPOINTS
    // ──────────────────────────────

    /**
     * Returns all endpoints defined for a specific collection within a provider.
     * e.g. $this->configuration->getEndpoints('my_custom_api_v1', 'collections', 'App\Entity\Book');
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Type of collections to retrieve ('collections' or 'authentication')
     * @param string $collection Fully qualified class name of the entity (e.g., 'App\Entity\Book')
     * @return array|null Returns an array of endpoints if the collection exists, null otherwise.
     */
    public function getEndpoints(?string $provider, ?string $segment, ?string $collection): array
    {
        if (
            ! $this->isCollectionEnabled($provider, $segment, $collection)
        ) return [];

        return $this->getCollection($provider, $segment, $collection)['endpoints'] ?? [];
    }

    /**
     * Returns a specific endpoint configuration.
     * e.g. $this->configuration->getEndpoint('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Type of collections to retrieve ('collections' or 'authentication')
     * @param string $collection Fully qualified class name of the entity (e.g., 'App\Entity\Book')
     * @param string $endpoint Name of the endpoint (e.g., 'index', 'show')
     * @return array|null Endpoint configuration array or null if not found.
     */
    public function getEndpoint(string $provider, string $segment, string $collection, string $endpoint): ?array
    {
        return $this->getEndpoints($provider, $segment, $collection)[$endpoint] ?? null;
    }

    /**
     * Check if a specific endpoint exists for a given collection within a provider.
     * e.g. $this->configuration->hasEndpoint('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Type of collections to retrieve ('collections' or 'authentication')
     * @param string $collection Fully qualified class name of the entity (e.g., 'App\Entity\Book')
     * @param string $endpoint Name of the endpoint (e.g., 'index', 'show')
     * @return bool True if the endpoint exists, false otherwise
     */
    public function hasEndpoint(string $provider, string $segment, string $collection, string $endpoint): bool
    {
        if (
            ! $this->hasProvider($provider) || 
            ! $this->hasCollection($provider, $segment, $collection)
        ) return false;

        return array_key_exists($endpoint, $this->getEndpoints($provider, $segment, $collection));
    }

    /**
     * Check if a specific endpoint is enabled.
     * e.g. $this->configuration->isEndpointEnabled('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Type of collections to retrieve ('collections' or 'authentication')
     * @param string $collection Fully qualified class name of the entity (e.g., 'App\Entity\Book')
     * @param string $endpoint Name of the endpoint (e.g., 'index', 'show')
     * @return bool True if the endpoint is enabled, false otherwise
     */
    public function isEndpointEnabled(string $provider, string $segment, string $collection, string $endpoint): bool
    {
        if (
            ! $this->hasProvider($provider) ||
            ! $this->hasCollection($provider, $segment, $collection) ||
            ! $this->hasEndpoint($provider, $segment, $collection, $endpoint)
        ) return false;

        return $this->getEndpoint($provider, $segment, $collection, $endpoint)['enabled']
            ?? $this->isCollectionEnabled($provider, $segment, $collection);
    }


    // ──────────────────────────────
    // DEPRECATION
    // ──────────────────────────────

    /**
     * Check if a specific API component (provider, collection, endpoint) is deprecated.
     * e.g.: $this->configuration->isDeprecationEnabled('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return bool True if the specified component is deprecated, false otherwise
     */
    public function isDeprecationEnabled(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        // 1. Provider-level deprecation
        $providerOptions = $this->getProvider($provider);
        if (isset($providerOptions['deprecation']['enabled']) && $providerOptions['deprecation']['enabled'] === true) {
            return true;
        }

        // 2. Collection-level deprecation
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
            if (isset($collectionOptions['deprecation']['enabled']) && $collectionOptions['deprecation']['enabled'] === true) {
                return true;
            }
        }

        // 3. Endpoint-level deprecation
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if (isset($endpointOptions['deprecation']['enabled']) && $endpointOptions['deprecation']['enabled'] === true) {
                return true;
            }
        }

        // 4. Default
        return false;
    }

    /**
     * Get the deprecation start date for a specific API component (provider, collection, endpoint).
     * e.g.: $this->configuration->getDeprecationStartAt('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return string|null Deprecation start date in 'YYYY-MM-DD' format, or null if not set 
     */
    public function getDeprecationStartAt(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): ?string
    {
        if (! $this->hasProvider($provider)) {
            return null;
        }

        if (!$this->isDeprecationEnabled($provider, $collection, $endpoint)) {
            return null;
        }

        // 1. Endpoint-level deprecation since date
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if (isset($endpointOptions['deprecation']['start_at'])) {
                return $endpointOptions['deprecation']['start_at'];
            }
        }

        // 2. Collection-level deprecation since date
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
            if (isset($collectionOptions['deprecation']['start_at'])) {
                return $collectionOptions['deprecation']['start_at'];
            }
        }

        // 3. Provider-level deprecation since date
        $providerOptions = $this->getProvider($provider);
        if (isset($providerOptions['deprecation']['start_at'])) {
            return $providerOptions['deprecation']['start_at'];
        }

        // 4. Default
        return null;
    }

    /**
     * Get the deprecation removal date for a specific API component (provider, collection, endpoint).
     * e.g.: $this->configuration->getDeprecationSunsetAt('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return string|null Deprecation removal date in 'YYYY-MM-DD' format, or null if not set
     */
    public function getDeprecationSunsetAt(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): ?string
    {
        if (! $this->hasProvider($provider)) {
            return null;
        }

        if (!$this->isDeprecationEnabled($provider, $collection, $endpoint)) {
            return null;
        }

        // 1. Endpoint-level deprecation removal date
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if (isset($endpointOptions['deprecation']['sunset_at'])) {
                return $endpointOptions['deprecation']['sunset_at'];
            }
        }

        // 2. Collection-level deprecation removal date
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
            if (isset($collectionOptions['deprecation']['sunset_at'])) {
                return $collectionOptions['deprecation']['sunset_at'];
            }
        }

        // 3. Provider-level deprecation removal date
        $providerOptions = $this->getProvider($provider);
        if (isset($providerOptions['deprecation']['sunset_at'])) {
            return $providerOptions['deprecation']['sunset_at'];
        }

        // 4. Default
        return null;
    }

    /**
     * Get the deprecation link for a specific API component (provider, collection, endpoint).
     * e.g.: $this->configuration->getDeprecationLink('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return string|null Deprecation link URL or route name, or null if not set
     */
    public function getDeprecationLink(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): ?string
    {
        if (! $this->hasProvider($provider)) {
            return null;
        }

        if (!$this->isDeprecationEnabled($provider, $collection, $endpoint)) {
            return null;
        }

        // 1. Endpoint-level deprecation link
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if (isset($endpointOptions['deprecation']['link'])) {
                return $this->resolveDeprecationLink($endpointOptions['deprecation']['link']);
            }
        }

        // 2. Collection-level deprecation link
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
            if (isset($collectionOptions['deprecation']['link'])) {
                return $this->resolveDeprecationLink($collectionOptions['deprecation']['link']);
            }
        }

        // 3. Provider-level deprecation link
        $providerOptions = $this->getProvider($provider);
        if (isset($providerOptions['deprecation']['link'])) {
            return $this->resolveDeprecationLink($providerOptions['deprecation']['link']);
        }

        // 4. Default
        return null;
    }

    /**
     * Get the deprecation successor link for a specific API component (provider, segment, collection, endpoint).
     * e.g.: $this->configuration->getDeprecationSuccessor('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return string|null Deprecation successor link URL or route name, or null if not set
     */
    public function getDeprecationSuccessor(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): ?string
    {
        if (! $this->hasProvider($provider)) {
            return null;
        }

        if (!$this->isDeprecationEnabled($provider, $collection, $endpoint)) {
            return null;
        }

        // 1. Endpoint-level deprecation successor link
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if (isset($endpointOptions['deprecation']['successor'])) {
                return $this->resolveDeprecationLink($endpointOptions['deprecation']['successor']);
            }
        }

        // 2. Collection-level deprecation successor link
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
            if (isset($collectionOptions['deprecation']['successor'])) {
                return $this->resolveDeprecationLink($collectionOptions['deprecation']['successor']);
            }
        }

        // 3. Provider-level deprecation successor link
        $providerOptions = $this->getProvider($provider);
        if (isset($providerOptions['deprecation']['successor'])) {
            return $this->resolveDeprecationLink($providerOptions['deprecation']['successor']);
        }

        // 4. Default
        return null;
    }

    /**
     * Get the deprecation message for a specific API component (provider, segment, collection, endpoint).
     * e.g.: $this->configuration->getDeprecationMessage('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return string|null Deprecation message, or null if not set
     */
    public function getDeprecationMessage(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): ?string
    {
        if (! $this->hasProvider($provider)) {
            return null;
        }

        if (!$this->isDeprecationEnabled($provider, $collection, $endpoint)) {
            return null;
        }

        // 1. Endpoint-level deprecation message
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if (isset($endpointOptions['deprecation']['message'])) {
                return $endpointOptions['deprecation']['message'];
            }
        }

        // 2. Collection-level deprecation message
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
            if (isset($collectionOptions['deprecation']['message'])) {
                return $collectionOptions['deprecation']['message'];
            }
        }

        // 3. Provider-level deprecation message
        $providerOptions = $this->getProvider($provider);
        if (isset($providerOptions['deprecation']['message'])) {
            return $providerOptions['deprecation']['message'];
        }

        // 4. Default
        return null;
    }

    /**
     * Resolve a deprecation link which can be either a full URL or a route name.
     * 
     * @param string $input Deprecation link input (URL or route name)
     * @return string|null Resolved URL or null if not resolvable
     */
    private function resolveDeprecationLink(string $input): ?string
    {
        if (preg_match('#^https?://#i', $input)) {
            return $input;
        }

        try {
            return $this->urlGenerator->generate(
                $input,
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        } catch (\Throwable $e) {
            // Route does not exist or requires parameters; return null
        }
        
        return null;
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
    public function getVersion(?string $provider): array
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
    public function getVersionNumber(?string $provider): int|null
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
    public function getVersionPrefix(?string $provider): string|null
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
    public function getVersionLocation(?string $provider): string|null
    {
        if (!$this->hasProvider($provider)) {
            return null;
        }

        $options = $this->getProvider($provider);
        $version = $options['version'] ?? null;

        return $version['location'] ?? null;
    }

    /**
     * Get version header directive for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Version header directive (e.g., 'Accept')
     */
    public function getVersionHeaderDirective(?string $provider): string|null
    {
        if (!$this->hasProvider($provider)) {
            return null;
        }

        $options = $this->getProvider($provider);
        $version = $options['version'] ?? null;

        return $version['directive'] ?? null;
    }

    /**
     * Get version header format for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Version header format (e.g., 'application/vnd.{vendor}.v{version}+json')
     */
    public function getVersionHeaderPattern(?string $provider): string|null
    {
        if (!$this->hasProvider($provider)) {
            return null;
        }

        $options = $this->getProvider($provider);
        $version = $options['version'] ?? null;

        return $version['pattern'] ?? null;
    }

    /**
     * Check if a specific API provider is marked as beta.
     * 
     * @param string $providerName Name of the API provider
     * @return bool True if the provider is beta, false otherwise
     */
    public function isVersionBeta(?string $provider): bool
    {
        if (!$this->hasProvider($provider)) {
            return false;
        }

        $options = $this->getProvider($provider);
        $version = $options['version'] ?? null;

        return $version['beta'] ?? false;
    }


    // ──────────────────────────────
    // ROUTE
    // ──────────────────────────────
    
    // Route configuration with fallback

    /**
     * Get the route configuration with optional fallback at endpoint, collection or provider level.
     * e.g.: $this->configuration->getRoute('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Route configuration array
     */
    public function getRoute(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific route
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['route'])) {
                return $endpointOptions['route'];
            }
        }

        // 2. Collection-level route
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
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
     * e.g.: $this->configuration->getRouteNamePattern('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return string Route name pattern
     */
    public function getRouteNamePattern(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): string
    {
        if (! $this->hasProvider($provider)) {
            return '';
        }

        // 1. Endpoint-specific pattern
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['route']['pattern'])) {
                return $endpointOptions['route']['pattern'];
            }
        }

        // 2. Collection-level pattern
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
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
    public function getRoutePrefix(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): string
    {
        if (! $this->hasProvider($provider)) {
            return '';
        }

        // 1. Endpoint-specific prefix
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['route']['prefix'])) {
                return $endpointOptions['route']['prefix'];
            }
        }

        // 2. Collection-level prefix
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
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
     * e.g.: $this->configuration->getRouteHosts('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Route hosts
     */
    public function getRouteHosts(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific hosts
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['route']['hosts'])) {
                return $endpointOptions['route']['hosts'];
            }
        }

        // 2. Collection-level hosts
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
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
     * e.g.: $this->configuration->getRouteSchemes('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Route schemes
     */
    public function getRouteSchemes(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific schemes
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['route']['schemes'])) {
                return $endpointOptions['route']['schemes'];
            }
        }

        // 2. Collection-level schemes
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
            if ($collectionOptions && isset($collectionOptions['route']['schemes'])) {
                return $collectionOptions['route']['schemes'];
            }
        }

        // 3. Global default schemes
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['routes']['schemes'] ?? [];
    }

    /**
     * Get the route name of a specific endpoint.
     * e.g.: $this->configuration->getRouteName('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Name of the segment
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return string|null Route name or null if not found
     */
    public function getRouteName(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): string|null
    {
        return $this->getRoute($provider, $segment, $collection, $endpoint)['name'] ?? null;
    }

    /**
     * Get the route path of a specific endpoint.
     * e.g.: $this->configuration->getRoutePath('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Name of the segment
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return string|null Route path or null if not found
     */
    public function getRoutePath(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): string|null
    {
        return $this->getRoute($provider, $segment, $collection, $endpoint)['path'] ?? null;
    }

    /**
     * Get the route methods of a specific endpoint.
     * e.g.: $this->configuration->getRouteMethods('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Name of the segment
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return array List of HTTP methods (GET, POST, etc.) or an empty array if not found
     */
    public function getRouteMethods(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): array
    {
        return $this->getRoute($provider, $segment, $collection, $endpoint)['methods'] ?? [];
    }

    /**
     * Get the route controller of a specific endpoint.
     * e.g.: $this->configuration->getRouteController('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     *
     * @param string $provider Name of the API provider
     * @param string $segment Name of the segment
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return string Route controller or an empty string if not found
     */
    public function getRouteController(?string $provider,  ?string $segment, ?string $collection, ?string $endpoint): string
    {
        return $this->getRoute($provider, $segment, $collection, $endpoint)['controller'] ?? '';
    }

    /**
     * Get the route options of a specific endpoint.
     * e.g.: $this->configuration->getRouteOptions('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     *
     * @param string $provider Name of the API provider
     * @param string $segment Name of the segment
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return array Route options or an empty array if not found
     */
    public function getRouteOptions(?string $provider,  ?string $segment, ?string $collection, ?string $endpoint): array
    {
        return $this->getRoute($provider, $segment, $collection, $endpoint)['options'] ?? [];
    }

    /**
     * Get the route requirements of a specific endpoint.
     * e.g.: $this->configuration->getRouteRequirements('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     *
     * @param string $provider Name of the API provider
     * @param string $segment Name of the segment
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return array Route requirements or an empty array if not found
     */
    public function getRouteRequirements(?string $provider,  ?string $segment, ?string $collection, ?string $endpoint): array
    {
        return $this->getRoute($provider, $segment, $collection, $endpoint)['requirements'] ?? [];
    }

    /**
     * Get the route condition of a specific endpoint.
     * e.g.: $this->configuration->getRouteCondition('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     *
     * @param string $provider Name of the API provider
     * @param string $segment Name of the segment
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return string Route condition or an empty string if not found
     */
    public function getRouteCondition(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): string
    {
        return $this->getRoute($provider, $segment, $collection, $endpoint)['condition'] ?? '';
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
    public function getPagination(?string $provider, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific route
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, ContextService::SEGMENT_COLLECTION, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['pagination'])) {
                return $endpointOptions['pagination'];
            }
        }

        // 2. Collection-level route
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, ContextService::SEGMENT_COLLECTION, $collection);
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
    public function isPaginationEnabled(?string $provider, ?string $collection = null, ?string $endpoint = null): bool
    {
        if (! $this->hasProvider($provider)) {
            return true;
        }

        return $this->getPaginationFlag($this->getEndpoint($provider, ContextService::SEGMENT_COLLECTION, $collection, $endpoint) ?? [])
            ?? $this->getPaginationFlag($this->getCollection($provider, ContextService::SEGMENT_COLLECTION, $collection) ?? [])
            ?? $this->getPaginationFlag($this->getProvider($provider) ?? [])
            ?? true;
    }

    /**
     * Get the number of items per page for pagination with optional fallback at endpoint, collection or provider level.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return int Number of items per page
     */
    public function getPaginationLimit(?string $provider, ?string $collection = null, ?string $endpoint = null): int
    {
        if (! $this->hasProvider($provider)) {
            return 10;
        }

        // 1. Endpoint-specific pagination limit
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, ContextService::SEGMENT_COLLECTION, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['pagination']['limit'])) {
                return $endpointOptions['pagination']['limit'];
            }
        }

        // 2. Collection-level pagination limit
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, ContextService::SEGMENT_COLLECTION, $collection);
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
    public function getPaginationMaxLimit(?string $provider, ?string $collection = null, ?string $endpoint = null): int
    {
        if (! $this->hasProvider($provider)) {
            return 100;
        }

        // 1. Endpoint-specific pagination max limit
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, ContextService::SEGMENT_COLLECTION, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['pagination']['max_limit'])) {
                return $endpointOptions['pagination']['max_limit'];
            }
        }

        // 2. Collection-level pagination max limit
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, ContextService::SEGMENT_COLLECTION, $collection);
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
    public function isPaginationLimitOverrideAllowed(?string $provider, ?string $collection = null, ?string $endpoint = null): bool
    {
        if (! $this->hasProvider($provider)) {
            return true;
        }

        // 1. Endpoint-specific pagination allow_limit_override
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, ContextService::SEGMENT_COLLECTION, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['pagination']['allow_limit_override'])) {
                return $endpointOptions['pagination']['allow_limit_override'];
            }
        }

        // 2. Collection-level pagination allow_limit_override
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, ContextService::SEGMENT_COLLECTION, $collection);
            if ($collectionOptions && isset($collectionOptions['pagination']['allow_limit_override'])) {
                return $collectionOptions['pagination']['allow_limit_override'];
            }
        }

        // 3. Global default pagination allow_limit_override
        return $this->getProvider($provider)['pagination']['allow_limit_override'] ?? true;
    }

    /**
     * Get the query parameter name for the page number in pagination.
     * 
     * @param string $provider Name of the API provider
     * @return string Query parameter name for page number
     */
    public function getParameterPage(?string $provider): string
    {
        if (! $this->hasProvider($provider)) {
            return 'page';
        }

        $options = $this->getPagination($provider);
        return $options['parameters']['page'] ?? 'page';
    }

    /**
     * Get the query parameter name for the limit in pagination.
     * 
     * @param string $provider Name of the API provider
     * @return string Query parameter name for limit
     */
    public function getParameterLimit(?string $provider): string
    {
        if (! $this->hasProvider($provider)) {
            return 'limit';
        }

        $options = $this->getPagination($provider);
        return $options['parameters']['limit'] ?? 'limit';
    }

    /**
     * Helper method to extract the pagination enabled flag from a configuration array.
     * 
     * @param array $config Configuration array
     * @return bool|null Pagination enabled flag or null if not set
     */
    private function getPaginationFlag(array $config): ?bool
    {
        return $config['pagination']['enabled'] ?? null;
    }


    // ──────────────────────────────
    // URL SUPPORT
    // ──────────────────────────────

    /**
     * Get the URL support configuration with optional fallback at collection or provider level.
     * e.g.: $this->configuration->getUrlSupport('my_custom_api_v1', 'collections', 'App\Entity\Book');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @return array URL support configuration array
     */
    public function getUrlSupport(?string $provider, ?string $segment, ?string $collection = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
            if ($collectionOptions && isset($collectionOptions['url'])) {
                return $collectionOptions['url'];
            }
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['url'] ?? [];
    }

    /**
     * Check if URL support is enabled with optional fallback at collection or provider level.
     * e.g.: $this->configuration->hasUrlSupport('my_custom_api_v1', 'collections', 'App\Entity\Book');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @return bool True if URL support is enabled, false otherwise
     */
    public function hasUrlSupport(?string $provider, ?string $segment, ?string $collection = null): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
            if ($collectionOptions && isset($collectionOptions['url']['support'])) {
                return true;
            }
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['url']['support'] ?? false;
    }
    
    /**
     * Check if URLs are absolute with optional fallback at collection or provider level.
     * e.g.: $this->configuration->isUrlAbsolute('my_custom_api_v1', 'collections', 'App\Entity\Book');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @return bool True if URLs are absolute, false otherwise
     */
    public function isUrlAbsolute(?string $provider, ?string $segment, ?string $collection = null): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
            if ($collectionOptions && isset($collectionOptions['url']['absolute'])) {
                return $collectionOptions['url']['absolute'];
            }
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['url']['absolute'] ?? false;
    }

    /**
     * Get the URL property name with optional fallback at collection or provider level.
     * e.g.: $this->configuration->getUrlProperty('my_custom_api_v1', 'collections', 'App\Entity\Book');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @return string URL property name
     */
    public function getUrlProperty(?string $provider, ?string $segment, ?string $collection = null): string
    {
        if (! $this->hasProvider($provider)) {
            return '';
        }

        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
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
     * e.g.: $this->configuration->getRateLimit('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Rate limit configuration array
     */
    public function getRateLimit(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific rate limit
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['rate_limit'])) {
                return $endpointOptions['rate_limit'];
            }
        }

        // 2. Collection-level rate limit
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
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
     * e.g.: $this->configuration->isRateLimitEnabled('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return bool True if rate limiting is enabled, false otherwise
     */
    public function isRateLimitEnabled(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        // 1. Endpoint-specific rate limit enable
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['rate_limit']['enabled'])) {
                return $endpointOptions['rate_limit']['enabled'];
            }
        }

        // 2. Collection-level rate limit enable
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
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
     * e.g.: $this->configuration->getRateLimitValue('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return string Rate limit value (e.g., '100/hour')
     */
    public function getRateLimitValue(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): string
    {
        if (! $this->hasProvider($provider)) {
            return '100/hour';
        }

        // 1. Endpoint-specific rate limit
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['rate_limit']['limit'])) {
                return $endpointOptions['rate_limit']['limit'];
            }
        }

        // 2. Collection-level rate limit
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
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
     * e.g.: $this->configuration->getRateLimitByRole('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Rate limit configuration by role
     */
    public function getRateLimitByRole(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific rate limit by_role
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['rate_limit']['by_role'])) {
                return $endpointOptions['rate_limit']['by_role'];
            }
        }

        // 2. Collection-level rate limit by_role
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
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
     * e.g.: $this->getRateLimitByUser('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Rate limit configuration by role
     */
    public function getRateLimitByUser(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific rate limit by_user
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['rate_limit']['by_user'])) {
                return $endpointOptions['rate_limit']['by_user'];
            }
        }

        // 2. Collection-level rate limit by_user
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
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
     * e.g.: $this->getDeleteTemplate('my_custom_api_v1', 'collections', 'App\Entity\Book', 'delete');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Rate limit configuration by user
     */
    public function getRateLimitByIp(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific rate limit by_ip
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['rate_limit']['by_ip'])) {
                return $endpointOptions['rate_limit']['by_ip'];
            }
        }

        // 2. Collection-level rate limit by_ip
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
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
     * e.g.: $this->getRateLimitByApplication('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Rate limit configuration by application
     */
    public function getRateLimitByApplication(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific rate limit by_application
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['rate_limit']['by_application'])) {
                return $endpointOptions['rate_limit']['by_application'];
            }
        }

        // 2. Collection-level rate limit by_application
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
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

    /**
     * Get the templates configuration with optional fallback at endpoint, collection or provider level.
     * e.g.: $this->getTemplates('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Templates configuration array
     */
    public function getTemplates(string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        // 1. Endpoint-specific templates
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['templates'])) {
                return $endpointOptions['templates'];
            }
        }

        // 2. Collection-level templates
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
            if ($collectionOptions && isset($collectionOptions['templates'])) {
                return $collectionOptions['templates'];
            }
        }

        // 3. Global default templates
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['templates'] ?? [];
    }

    /**
     * Get the list template for a specific provider, collection, and endpoint.
     * Falls back to collection-level or provider-level templates if not found.
     * e.g.: $this->getListTemplate('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return string List template
     */
    public function getListTemplate(string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): string
    {
        if (! $this->hasProvider($provider)) {
            return '';
        }

        // 1. Endpoint-specific templates
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['templates']['list'])) {
                return $endpointOptions['templates']['list'];
            }
        }

        // 2. Collection-level templates
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
            if ($collectionOptions && isset($collectionOptions['templates']['list'])) {
                return $collectionOptions['templates']['list'];
            }
        }

        // 3. Global default templates
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['templates']['list'] ?? '';
    }

    /**
     * Get the not found template for a specific provider, collection, and endpoint.
     * Falls back to collection-level or provider-level templates if not found.
     * e.g.: $this->getNotFoundTemplate('my_custom_api_v1', 'collections', 'App\Entity\Book', 'show');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return string Not found template
     */
    public function getSingleTemplate(string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): string
    {
        if (! $this->hasProvider($provider)) {
            return '';
        }

        // 1. Endpoint-specific templates
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['templates']['single'])) {
                return $endpointOptions['templates']['single'];
            }
        }

        // 2. Collection-level templates
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
            if ($collectionOptions && isset($collectionOptions['templates']['single'])) {
                return $collectionOptions['templates']['single'];
            }
        }

        // 3. Global default templates
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['templates']['single'] ?? '';
    }

    /**
     * Get the delete template for a specific provider, collection, and endpoint.
     * Falls back to collection-level or provider-level templates if not found.
     * * e.g.: $this->getDeleteTemplate('my_custom_api_v1', 'collections', 'App\Entity\Book', 'delete');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return string Delete template
     */
    public function getDeleteTemplate(string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): string
    {
        if (! $this->hasProvider($provider)) {
            return '';
        }

        // 1. Endpoint-specific templates
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['templates']['delete'])) {
                return $endpointOptions['templates']['delete'];
            }
        }

        // 2. Collection-level templates
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
            if ($collectionOptions && isset($collectionOptions['templates']['delete'])) {
                return $collectionOptions['templates']['delete'];
            }
        }

        // 3. Global default templates
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['templates']['delete'] ?? '';
    }

    /**
     * Get the account template for a specific provider, collection, and endpoint.
     * Falls back to collection-level or provider-level templates if not found.
     * e.g.: $this->getAccountTemplate('my_custom_api_v1', 'collections', 'App\Entity\Book', 'index');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return string Account template
     */
    public function getAccountTemplate(string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): string
    {
        if (! $this->hasProvider($provider)) {
            return '';
        }

        // 1. Endpoint-specific templates
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['templates']['account'])) {
                return $endpointOptions['templates']['account'];
            }
        }

        // 2. Collection-level templates
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
            if ($collectionOptions && isset($collectionOptions['templates']['account'])) {
                return $collectionOptions['templates']['account'];
            }
        }

        // 3. Global default templates
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['templates']['account'] ?? '';
    }

    /**
     * Get the not found template for a specific provider, collection, and endpoint.
     * Falls back to collection-level or provider-level templates if not found.
     * e.g.: $this->getErrorTemplate('my_custom_api_v1', 'collections', 'App\Entity\Book', 'show');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return string Not found template
     */
    public function getErrorTemplate(string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): string
    {
        if (! $this->hasProvider($provider)) {
            return '';
        }

        // 1. Endpoint-specific templates
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['templates']['error'])) {
                return $endpointOptions['templates']['error'];
            }
        }

        // 2. Collection-level templates
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
            if ($collectionOptions && isset($collectionOptions['templates']['error'])) {
                return $collectionOptions['templates']['error'];
            }
        }

        // 3. Global default templates
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['templates']['error'] ?? '';
    }

    /**
     * Get the not found template for a specific API provider, collection, and endpoint.
     * Falls back to collection-level or provider-level templates if not defined at lower levels.
     * e.g.: $this->getNotFoundTemplate('my_custom_api_v1', 'collections', 'App\Entity\Book', 'show');
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return string Not found template
     */
    public function getNotFoundTemplate(string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): string
    {
        if (! $this->hasProvider($provider)) {
            return '';
        }

        // 1. Endpoint-specific templates
        if ($collection && $endpoint) {
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['templates']['not_found'])) {
                return $endpointOptions['templates']['not_found'];
            }
        }

        // 2. Collection-level templates
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
            if ($collectionOptions && isset($collectionOptions['templates']['not_found'])) {
                return $collectionOptions['templates']['not_found'];
            }
        }

        // 3. Global default templates
        $providerOptions = $this->getProvider($provider);
        return $providerOptions['templates']['not_found'] ?? '';
    }


    // ──────────────────────────────
    // RESPONSES
    // ──────────────────────────────

    /**
     * Get the response configuration for a specific provider.
     * 
     * @param string $provider Name of the API provider
     * @return array Response configuration array
     */
    public function getResponse(?string $provider): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['response'] ?? [];
    }


    // RESPONSE -> FORMAT

    /**
     * Get the response format for a specific provider.
     * Defaults to 'json' if not specified.
     * 
     * @param string $provider Name of the API provider
     * @return string Response format (e.g., 'json', 'xml')
     */
    public function getResponseType(?string $provider): string
    {
        if (! $this->hasProvider($provider)) {
            return 'json';
        }

        $responseOptions = $this->getResponse($provider);
        return $responseOptions['format']['type'] ?? 'json';
    }

    public function getResponseMimeType(?string $provider): ?string
    {
        if (! $this->hasProvider($provider)) {
            return null;
        }

        $responseOptions = $this->getResponse($provider);
        return $responseOptions['format']['mime_type'] ?? null;
    }

    /**
     * Check if format overrides are allowed for a specific provider.
     * 
     * @param string $provider Name of the API provider
     * @return bool True if format overrides are allowed, false otherwise
     */
    public function canOverrideResponseType(?string $provider): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        $responseOptions = $this->getResponse($provider);
        return $responseOptions['format']['override'] ?? false;
    }

    /**
     * Get the response format parameter name for a specific provider.
     * Defaults to '_format' if not specified.
     * 
     * @param string $provider Name of the API provider
     * @return string Response format parameter name
     */
    public function getResponseFormatParameter(?string $provider): string
    {
        if (! $this->hasProvider($provider)) {
            return '_format';
        }

        $responseOptions = $this->getResponse($provider);
        return $responseOptions['format']['parameter'] ?? '_format';
    }


    // RESPONSES -> CHECKSUM

    /**
     * Check if response checksum is enabled for a specific provider.
     * 
     * @param string $provider Name of the API provider
     * @return bool True if response checksum is enabled, false otherwise
     */
    public function isChecksumEnabled(?string $provider): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        $options = $this->getResponse($provider);
        return $options['checksum']['enabled'] ?? false;
    }

    /**
     * Get the response checksum algorithm for a specific provider.
     * Defaults to 'md5' if not specified.
     * 
     * @param string $provider Name of the API provider
     * @return string Checksum algorithm (e.g., 'md5', 'sha256')
     */
    public function getChecksumAlgorithm(?string $provider): string
    {
        if (! $this->hasProvider($provider)) {
            return 'md5';
        }

        $options = $this->getResponse($provider);
        return $options['checksum']['algorithm'] ?? 'md5';
    }


    // RESPONSE -> CACHE CONTROL

    /**
     * Check if response caching is enabled for a specific provider.
     * 
     * @param string $provider Name of the API provider
     * @return bool True if response caching is enabled, false otherwise
     */
    public function isCacheControlEnabled(?string $provider): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['response']['cache_control']['enabled'] ?? false;
    }

    /**
     * Check if response caching is public for a specific provider.
     * 
     * @param string $provider Name of the API provider
     * @return bool True if response caching is public, false otherwise
     */
    public function isCacheControlPublic(?string $provider): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['response']['cache_control']['public'] ?? false;
    }

    /**
     * Check if response caching has no-store directive for a specific provider.
     * 
     * @param string $provider Name of the API provider
     * @return bool True if response caching has no-store directive, false otherwise
     */
    public function isCacheControlNoStore(?string $provider): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['response']['cache_control']['no_store'] ?? false;
    }

    /**
     * Check if response caching has must-revalidate directive for a specific provider.
     * 
     * @param string $provider Name of the API provider
     * @return bool True if response caching has must-revalidate directive, false otherwise
     */
    public function isCacheControlMustRevalidate(?string $provider): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['response']['cache_control']['must_revalidate'] ?? false;
    }

    /**
     * Get the max-age value for response caching for a specific provider.
     * 
     * @param string $provider Name of the API provider
     * @return int Max-age value in seconds
     */
    public function getCacheControlMaxAge(?string $provider): int
    {
        if (! $this->hasProvider($provider)) {
            return 0;
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['response']['cache_control']['max_age'] ?? 0;
    }


    // RESPONSE -> HEADERS

    public function getHeaders(?string $provider): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['response']['headers'] ?? [];
    }

    public function isHeadersStripXPrefix(?string $provider): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['response']['headers']['strip_x_prefix'] ?? false;
    }

    public function isHeadersKeepLegacy(?string $provider): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['response']['headers']['keep_legacy'] ?? false;
    }

    public function getHeadersExposedDirectives(?string $provider): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['response']['headers']['exposed'] ?? [];
    }

    public function getHeadersVaryDirectives(?string $provider): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['response']['headers']['vary'] ?? [];
    }

    public function getHeadersCustomDirectives(?string $provider): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        $providerOptions = $this->getProvider($provider);

        $directives = $providerOptions['response']['headers']['custom'] ?? [];
        $directives = array_map('trim', $directives);
        $directives = array_combine(array_map(fn($key) => "X-{$key}", array_keys($directives)), array_values($directives));
        return $directives;
    }

    public function getHeadersRemoveDirectives(?string $provider): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['response']['headers']['remove'] ?? [];
    }


    // RESPONSE -> CORS

    public function getCorsAllowedOrigins(?string $provider): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['response']['headers']['cors']['origins'] ?? [];
    }

    public function getCorsAllowedMethods(?string $provider): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['response']['headers']['cors']['methods'] ?? [];
    }

    public function getCorsAttributes(?string $provider): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['response']['headers']['cors']['attributes'] ?? [];
    }

    public function getCorsCredentials(?string $provider): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['response']['headers']['cors']['credentials'] ?? false;
    }


    // RESPONSE -> COMPRESSION

    /**
     * Check if response compression is enabled for a specific provider.
     * 
     * @param string $provider Name of the API provider
     * @return bool True if response compression is enabled, false otherwise
     */
    public function isCompressionEnabled(?string $provider): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['response']['compression']['enabled'] ?? false;
    }

    /**
     * Get the compression level for a specific provider.
     * Defaults to 6 if not specified.
     * 
     * @param string $provider Name of the API provider
     * @return int Compression level (0-9)
     */
    public function getCompressionLevel(?string $provider): int
    {
        if (! $this->hasProvider($provider)) {
            return 6;
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['response']['compression']['level'] ?? 6;
    }
    
    /**
     * Get the compression format for a specific provider.
     * Defaults to 'gzip' if not specified.
     * 
     * @param string $provider Name of the API provider
     * @return string Compression format (e.g., 'gzip', 'deflate')
     */
    public function getCompressionFormat(?string $provider): string
    {
        if (! $this->hasProvider($provider)) {
            return 'gzip';
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['response']['compression']['format'] ?? 'gzip';
    }


    // ──────────────────────────────
    // SERIALIZATION
    // ──────────────────────────────

    /**
     * Get the serializer groups for a specific provider, collection, and endpoint.
     * e.g., getSerializerGroups('provider1', 'collections', 'users', 'getUser')
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Name of the segment
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return array Array of serializer groups
     */
    public function getSerializerGroups(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        if (! $this->hasCollection($provider, $segment, $collection)) {
            return [];
        }

        $collectionOptions = $this->getCollection($provider, $segment, $collection);
        $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);

        return array_unique(array_merge(
            $collectionOptions['serialization']['groups'] ?? [],
            $endpointOptions['serialization']['groups'] ?? []
        ));
    }

    /**
     * Get the serializer ignore fields for a specific provider, collection, and endpoint.
     * Merges ignore fields from provider and endpoint levels.
     * e.g., getSerializerIgnore('provider1', 'segmentA', 'users', 'getUser')
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Name of the segment
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return array Array of fields to ignore during serialization
     */
    public function getSerializerIgnore(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        $providerOptions = $this->getProvider($provider);

        if (! $this->hasCollection($provider, $segment, $collection)) {
            return $providerOptions['serialization']['ignore'] ?? [];
        }

        $collectionOptions = $this->getCollection($provider, $segment, $collection);
        $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);

        return array_unique(array_merge(
            $providerOptions['serialization']['ignore'] ?? [],
            $collectionOptions['serialization']['ignore'] ?? [],
            $endpointOptions['serialization']['ignore'] ?? []
        ));
    }

    /**
     * Get the serializer datetime format for a specific provider.
     * 
     * @param string $provider Name of the API provider
     * @return string Datetime format string (e.g., 'Y-m-d H:i:s')
     */
    public function getSerializerDatetimeFormat(?string $provider): string
    {
        if (! $this->hasProvider($provider)) {
            return 'Y-m-d H:i:s';
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['serialization']['datetime']['format'] ?? 'Y-m-d H:i:s';
    }

    /**
     * Get the serializer timezone for a specific provider.
     * 
     * @param string $provider Name of the API provider
     * @return string Timezone string (e.g., 'UTC', 'America/New_York')
     */
    public function getSerializerTimezone(?string $provider): string
    {
        if (! $this->hasProvider($provider)) {
            return 'UTC';
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['serialization']['datetime']['timezone'] ?? 'UTC';
    }

    /**
     * Check if null values should be skipped during serialization for a specific provider.
     * 
     * @param string $provider Name of the API provider
     * @return bool True if null values should be skipped, false otherwise
     */
    public function getSerializerSkipNull(?string $provider): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['serialization']['skip_null'] ?? false;
    }

    /**
     * Get the serializer transformer for a specific provider, segment, collection, and endpoint.
     * e.g., getSerializerTransformer('provider1', 'segmentA', 'users', 'getUser')
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Name of the segment (optional)
     * @param string $collection Name of the collection (optional)
     * @param string $endpoint Name of the endpoint (optional)
     * @return string|null Fully qualified class name of the serializer transformer, or null if not defined
     */
    public function getSerializerTransformer(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): ?string
    {
        if (! $this->hasProvider($provider)) {
            return null;
        }

        if (! $this->hasCollection($provider, $segment, $collection)) {
            return null;
        }

        $collectionOptions = $this->getCollection($provider, $segment, $collection);
        $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);

        return $endpointOptions['serialization']['transformer'] 
            ?? $collectionOptions['serialization']['transformer'] 
            ?? null
        ;
    }


    // ──────────────────────────────
    // ACCESS CONTROL
    // ──────────────────────────────

    /**
     * Get the access control configuration for a specific provider, segment, collection, and endpoint.
     * Merges access control settings from provider, segment, collection, and endpoint levels.
     * e.g., getAccessControl('provider1', 'segmentA', 'users', 'getUser')  
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Access control configuration array
     */
    public function getAccessControl(?string $provider,  ?string $segment, ?string $collection = null, ?string $endpoint = null): array
    {
        $defaults = [
            'merge' => 'append',
            'roles' => [],
            'voter' => null,
        ];

        if (! $this->hasProvider($provider)) {
            return $defaults;
        }

        // 1. Endpoint-specific access control
        if ($collection && $endpoint) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
            $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
            return array_merge(
                $defaults, 
                $collectionOptions['access_control'] ?? [], 
                $endpointOptions['access_control'] ?? []
            );
        }

        // 2. Collection-level access control
        if ($collection) {
            $collectionOptions = $this->getCollection($provider, $segment, $collection);
            return array_merge(
                $defaults, 
                $collectionOptions['access_control'] ?? []
            );
        }

        // 3. Global default access control
        $providerOptions = $this->getProvider($provider);
        return array_merge(
            $defaults, 
            $providerOptions['access_control'] ?? []
        );
    }

    /**
     * Get the access control roles for a specific provider, collection, and endpoint.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Array of access control roles
     */
    public function getAccessControlRoles(?string $provider, ?string $collection = null, ?string $endpoint = null): array
    {
        return $this->getAccessControl($provider, $collection, $endpoint)['roles'] ?? [];
    }

    /**
     * Get the access control voter for a specific provider, collection, and endpoint.
     * 
     * @param string $provider Name of the API provider
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return string Voter class name
     */
    public function getAccessControlVoter(?string $provider, ?string $collection = null, ?string $endpoint = null): string
    {
        return $this->getAccessControl($provider, $collection, $endpoint)['voter'] ?? '';
    }


    // ──────────────────────────────
    // AUTHENTICATION
    // ──────────────────────────────

    // // Authentication -> PROVIDER

    // /**
    //  * Get the security configuration for a specific API provider.
    //  * 
    //  * @param string $provider Name of the API provider
    //  * @return array Security configuration array
    //  */
    // public function getAuthentication(?string $provider): array
    // {
    //     if (! $this->hasProvider($provider)) {
    //         return [];
    //     }

    //     return $this->getProvider($provider)['authentication'] ?? [];
    // }


    // // AUTHENTICATION -> COLLECTIONS

    // public function getAuthenticationName(?string $provider, ?string $collection): string 
    // {
    //     if (! $this->hasProvider($provider)) {
    //         return '';
    //     }

    //     return $this->getAuthentication($provider)[$collection]['name'] ?? '';
    // }

    // public function getAuthenticationRoutePrefix(?string $provider, ?string $collection): string 
    // {
    //     if (! $this->hasProvider($provider)) {
    //         return '';
    //     }

    //     return $this->getAuthentication($provider)[$collection]['route']['prefix'] ?? '';
    // }

    // public function getAuthenticationRouteHosts(?string $provider, ?string $collection): string 
    // {
    //     if (! $this->hasProvider($provider)) {
    //         return '';
    //     }

    //     return $this->getAuthentication($provider)[$collection]['route']['hosts'] ?? '';
    // }

    // public function getAuthenticationRouteSchemes(?string $provider, ?string $collection): string 
    // {
    //     if (! $this->hasProvider($provider)) {
    //         return '';
    //     }

    //     return $this->getAuthentication($provider)[$collection]['route']['schemes'] ?? '';
    // }


    // // AUTHENTICATION -> ENDPOINTS

    // public function isAuthenticationEndpointEnabled(?string $provider, ?string $collection, ?string $endpoint): bool 
    // {
    //     if (! $this->hasProvider($provider)) {
    //         return false;
    //     }

    //     return $this->getAuthentication($provider)[$collection][$endpoint]['enabled'] ?? false;
    // }

    // public function getAuthenticationEndpointPath(?string $provider, ?string $collection, ?string $endpoint): string 
    // {
    //     if (! $this->hasProvider($provider)) {
    //         return '';
    //     }

    //     return $this->getAuthentication($provider)[$collection][$endpoint]['path'] ?? '';
    // }

    // public function getAuthenticationEndpointHosts(?string $provider, ?string $collection, ?string $endpoint): array 
    // {
    //     if (! $this->hasProvider($provider)) {
    //         return [];
    //     }

    //     return $this->getAuthentication($provider)[$collection][$endpoint]['hosts'] ?? [];
    // }

    // public function getAuthenticationEndpointSchemes(?string $provider, ?string $collection, ?string $endpoint): array 
    // {
    //     if (! $this->hasProvider($provider)) {
    //         return [];
    //     }

    //     return $this->getAuthentication($provider)[$collection][$endpoint]['schemes'] ?? [];
    // }

    // public function getAuthenticationEndpointController(?string $provider, ?string $collection, ?string $endpoint): ?string 
    // {
    //     if (! $this->hasProvider($provider)) {
    //         return null;
    //     }

    //     return $this->getAuthentication($provider)[$collection][$endpoint]['controller'] ?? null;
    // }

    // public function getAuthenticationEndpointProperties(?string $provider, ?string $collection, ?string $endpoint): array 
    // {
    //     if (! $this->hasProvider($provider)) {
    //         return [];
    //     }

    //     return $this->getAuthentication($provider)[$collection][$endpoint]['properties'] ?? [];
    // }










    /**
     * Get the security entity class for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Fully qualified class name of the security entity
     */
    // public function getSecurityClass(string $providerName): string
    // {
    //     $security = $this->getSecurity($providerName);
    //     return $security['entity']['class'] ?? '';
    // }

    /**
     * Get the security identifier property for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Identifier property name
     */
    // public function getSecurityIdentifierProperty(string $providerName): string
    // {
    //     $security = $this->getSecurity($providerName);
    //     return $security['entity']['identifier'] ?? '';
    // }

    // /**
    //  * Get the security password property for a specific API provider.
    //  * 
    //  * @param string $providerName Name of the API provider
    //  * @return string Password property name
    //  */
    // public function getSecurityPasswordProperty(string $providerName): string
    // {
    //     $security = $this->getSecurity($providerName);
    //     return $security['entity']['password'] ?? '';
    // }

    // /**
    //  * Get the security group property for a specific API provider.
    //  * 
    //  * @param string $providerName Name of the API provider
    //  * @return string Group property name
    //  */
    // public function getSecurityGroup(string $providerName): string
    // {
    //     $security = $this->getSecurity($providerName);
    //     return $security['group'] ?? '';
    // }



    // // Generic endpoint

    // public function isSecurityEndpointEnabled(string $provider, string $endpoint): bool
    // {
    //     $registration = $this->getRegistration($provider);
    //     $authentication = $this->getAuthentication($provider);
    //     $password = $this->getPassword($provider);

    //     return $registration[$endpoint]['enabled'] 
    //         ?? $authentication[$endpoint]['enabled'] 
    //         ?? $password[$endpoint]['enabled'] 
    //         ?? false
    //     ;
    // }

    // public function getSecurityEndpointPath(string $provider, string $endpoint): ?string
    // {
    //     $registration = $this->getRegistration($provider);
    //     $authentication = $this->getAuthentication($provider);
    //     $password = $this->getPassword($provider);

    //     return $registration[$endpoint]['path'] 
    //         ?? $authentication[$endpoint]['path'] 
    //         ?? $password[$endpoint]['path'] 
    //         ?? null
    //     ;
    // }

    // public function getSecurityEndpointHosts(string $provider, string $endpoint): array
    // {
    //     $registration = $this->getRegistration($provider);
    //     $authentication = $this->getAuthentication($provider);
    //     $password = $this->getPassword($provider);

    //     return $registration[$endpoint]['hosts'] 
    //         ?? $authentication[$endpoint]['hosts'] 
    //         ?? $password[$endpoint]['hosts'] 
    //         ?? [];
    // }

    // public function getSecurityEndpointSchemes(string $provider, string $endpoint): array
    // {
    //     $registration = $this->getRegistration($provider);
    //     $authentication = $this->getAuthentication($provider);
    //     $password = $this->getPassword($provider);
    //     return $registration[$endpoint]['schemes'] 
    //         ?? $authentication[$endpoint]['schemes'] 
    //         ?? $password[$endpoint]['schemes'] 
    //         ?? [];
    // }

    // public function getSecurityEndpointController(string $provider, string $endpoint): ?string
    // {
    //     $registration = $this->getRegistration($provider);
    //     $authentication = $this->getAuthentication($provider);
    //     $password = $this->getPassword($provider);

    //     return $registration[$endpoint]['controller'] 
    //         ?? $authentication[$endpoint]['controller'] 
    //         ?? $password[$endpoint]['controller'] 
    //         ?? null
    //     ;
    // }
    




    // Endpoint : Registration

    /**
     * Get the registration configuration for a specific API provider.
     * 
     * @param string $provider Name of the API provider
     * @return array Registration configuration array
     */
    // public function getRegistration(string $provider): array
    // {
    //     $security = $this->getSecurity($provider);
    //     return $security['registration'] ?? [];
    // }

    // public function isRegistrationEnabled(string $provider): bool
    // {
    //     $registration = $this->getRegistration($provider);
    //     return $registration['register']['enabled'] ?? false;
    // }

    // public function getRegistrationPath(string $provider): string
    // {
    //     $registration = $this->getRegistration($provider);
    //     return $registration['register']['path'] ?? '';
    // }

    // public function getRegistrationController(string $provider): string
    // {
    //     $registration = $this->getRegistration($provider);
    //     return $registration['register']['controller'] ?? '';
    // }

    // public function getRegistrationHosts(string $provider): array
    // {
    //     $registration = $this->getRegistration($provider);
    //     return $registration['register']['hosts'] ?? [];
    // }

    // public function getRegistrationSchemes(string $provider): array
    // {
    //     $registration = $this->getRegistration($provider);
    //     return $registration['register']['schemes'] ?? [];
    // }

    // public function getRegistrationFieldsMapping(string $provider): array
    // {
    //     $registration = $this->getRegistration($provider);
    //     return $registration['register']['fields'] ?? [];
    // }


    // public function isEmailVerificationEnabled(string $provider): bool
    // {
    //     $registration = $this->getRegistration($provider);
    //     return $registration['verify_email']['enabled'] ?? false;
    // }

    // public function getEmailVerificationPath(string $provider): string
    // {
    //     $registration = $this->getRegistration($provider);
    //     return $registration['verify_email']['path'] ?? '';
    // }

    // public function getEmailVerificationController(string $provider): string
    // {
    //     $registration = $this->getRegistration($provider);
    //     return $registration['verify_email']['controller'] ?? '';
    // }


    // public function isResendVerificationEnabled(string $provider): bool
    // {
    //     $registration = $this->getRegistration($provider);
    //     return $registration['resend_verification']['enabled'] ?? false;
    // }

    // public function getResendVerificationPath(string $provider): string
    // {
    //     $registration = $this->getRegistration($provider);
    //     return $registration['resend_verification']['path'] ?? '';
    // }

    // public function getResendVerificationController(string $provider): string
    // {
    //     $registration = $this->getRegistration($provider);
    //     return $registration['resend_verification']['controller'] ?? '';
    // }

    // Endpoint : Authentication

    /**
     * Get the authentication configuration for a specific API provider.
     * 
     * @param string $provider Name of the API provider
     * @return array Authentication configuration array
     */
    // public function getAuthentication(string $provider): array
    // {
    //     $security = $this->getSecurity($provider);
    //     return $security['authentication'] ?? [];
    // }


    // public function isLoginEnabled(string $provider): bool
    // {
    //     $authentication = $this->getAuthentication($provider);
    //     return $authentication['login']['enabled'] ?? false;
    // }

    // public function getLoginPath(string $provider): string
    // {
    //     $authentication = $this->getAuthentication($provider);
    //     return $authentication['login']['path'] ?? '';
    // }

    // public function getLoginController(string $provider): string
    // {
    //     $authentication = $this->getAuthentication($provider);
    //     return $authentication['login']['controller'] ?? '';
    // }


    // public function isLogoutEnabled(string $provider): bool
    // {
    //     $authentication = $this->getAuthentication($provider);
    //     return $authentication['logout']['enabled'] ?? false;
    // }

    // public function getLogoutPath(string $provider): string
    // {
    //     $authentication = $this->getAuthentication($provider);
    //     return $authentication['logout']['path'] ?? '';
    // }

    // public function getLogoutController(string $provider): string
    // {
    //     $authentication = $this->getAuthentication($provider);
    //     return $authentication['logout']['controller'] ?? '';
    // }


    // public function isRefreshTokenEnabled(string $provider): bool
    // {
    //     $authentication = $this->getAuthentication($provider);
    //     return $authentication['refresh_token']['enabled'] ?? false;
    // }

    // public function getRefreshTokenPath(string $provider): string
    // {
    //     $authentication = $this->getAuthentication($provider);
    //     return $authentication['refresh_token']['path'] ?? '';
    // }

    // public function getRefreshTokenController(string $provider): string
    // {
    //     $authentication = $this->getAuthentication($provider);
    //     return $authentication['refresh_token']['controller'] ?? '';
    // }

    // // Endpoint : Password

    // /**
    //  * Get the password configuration for a specific API provider.
    //  * 
    //  * @param string $provider Name of the API provider
    //  * @return array Password configuration array
    //  */
    // public function getPassword(string $provider): array
    // {
    //     $security = $this->getSecurity($provider);
    //     return $security['password'] ?? [];
    // }


    // public function isResetRequestEnabled(string $provider): bool
    // {
    //     $authentication = $this->getAuthentication($provider);
    //     return $authentication['reset_request']['enabled'] ?? false;
    // }

    // public function getResetRequestPath(string $provider): string
    // {
    //     $authentication = $this->getAuthentication($provider);
    //     return $authentication['reset_request']['path'] ?? '';
    // }

    // public function getResetRequestController(string $provider): string
    // {
    //     $authentication = $this->getAuthentication($provider);
    //     return $authentication['reset_request']['controller'] ?? '';
    // }


    // public function isResetConfirmEnabled(string $provider): bool
    // {
    //     $authentication = $this->getAuthentication($provider);
    //     return $authentication['reset_confirm']['enabled'] ?? false;
    // }

    // public function getResetConfirmPath(string $provider): string
    // {
    //     $authentication = $this->getAuthentication($provider);
    //     return $authentication['reset_confirm']['path'] ?? '';
    // }

    // public function getResetConfirmController(string $provider): string
    // {
    //     $authentication = $this->getAuthentication($provider);
    //     return $authentication['reset_confirm']['controller'] ?? '';
    // }


    // public function isChangePasswordEnabled(string $provider): bool
    // {
    //     $authentication = $this->getAuthentication($provider);
    //     return $authentication['change_password']['enabled'] ?? false;
    // }

    // public function getChangePasswordPath(string $provider): string
    // {
    //     $authentication = $this->getAuthentication($provider);
    //     return $authentication['change_password']['path'] ?? '';
    // }

    // public function getChangePasswordController(string $provider): string
    // {
    //     $authentication = $this->getAuthentication($provider);
    //     return $authentication['change_password']['controller'] ?? '';
    // }





    // ──────────────────────────────
    // REPOSITORY
    // ──────────────────────────────

    /**
     * Get the repository configuration for a specific provider, segment, collection, and endpoint.
     * e.g.: getRepository('my_custom_api', 'v1', 'users', 'list')
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Name of the API segment
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return array Repository configuration array
     */
    public function getRepository(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        if (! $this->hasCollection($provider, $segment, $collection)) {
            return [];
        }

        if (! $this->hasEndpoint($provider, $segment, $collection, $endpoint)) {
            return [];
        }

        $endpointOptions = $this->getEndpoint($provider, $segment, $collection, $endpoint);
        return $endpointOptions['repository'] ?? [];
    }

    /**
     * Get the repository class for a specific provider, segment, collection, and endpoint.
     * e.g.: getRepositoryClass('my_custom_api', 'v1', 'users', 'list')
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Name of the API segment
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return string|null Fully qualified class name of the repository, or null if not defined
     */
    public function getRepositoryClass(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): ?string
    {
        return $this->getEndpoint($provider, $segment, $collection, $endpoint)['repository']['class'] ?? null;
    }

    /**
     * Get the repository method for a specific provider, segment, collection, and endpoint.
     * e.g.: getRepositoryMethod('my_custom_api', 'v1', 'users', 'list')
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Name of the API segment
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return string|null Repository method name, or null if not defined
     */
    public function getRepositoryMethod(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): ?string
    {
        return $this->getEndpoint($provider, $segment, $collection, $endpoint)['repository']['method'] ?? null;
    }

    /**
     * Get the criteria configuration for a specific provider, segment, collection, and endpoint.
     * e.g.: getCriteria('my_custom_api', 'v1', 'users', 'list')
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Name of the API segment
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return array Criteria configuration array
     */
    public function getCriteria(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): array
    {
        return $this->getEndpoint($provider, $segment, $collection, $endpoint)['repository']['criteria'] ?? [];
    }

    /**
     * Get the order by configuration for a specific provider, segment, collection, and endpoint.
     * e.g.: getOrderBy('my_custom_api', 'v1', 'users', 'list')
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Name of the API segment
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return array Order by configuration array
     */
    public function getOrderBy(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): array
    {
        return $this->getEndpoint($provider, $segment, $collection, $endpoint)['repository']['order_by'] ?? [];
    }

    /**
     * Get the limit for a specific provider, segment, collection, and endpoint.
     * e.g.: getLimit('my_custom_api', 'v1', 'users', 'list')
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Name of the API segment
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return int|null Limit value, or null if not defined
     */
    public function getLimit(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): ?int
    {
        $endpoint = $this->getEndpoint($provider, $segment, $collection, $endpoint);
        $limit = $endpoint['repository']['limit'] ?? null;

        if (!empty($limit) && is_int($limit) && $limit > 0) {
            return $limit;
        }

        if ($this->isPaginationEnabled($provider)) {
            return $this->getPaginationLimit($provider, $collection);
        }

        return null;
    }

    /**
     * Get the fetch mode for a specific provider, segment, collection, and endpoint.
     * e.g.: getFetchMode('my_custom_api', 'v1', 'users', 'list')
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Name of the API segment
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @return string Fetch mode (e.g., 'eager', 'lazy')
     */
    public function getFetchMode(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): string
    {
        return $this->getEndpoint($provider, $segment, $collection, $endpoint)['repository']['fetch_mode'] ?? '';
    }

    
    // ──────────────────────────────
    // META
    // ──────────────────────────────

    /**
     * Get the metadata for a specific provider, segment, collection, and endpoint.
     * e.g.: getAllMetadata('my_custom_api', 'v1', 'users', 'list')
     * 
     * @param string $provider Name of the API provider
     * @param string|null $segment Name of the API segment (optional)
     * @param string|null $collection Name of the collection (optional)
     * @param string|null $endpoint Name of the endpoint (optional)
     * @return array Array of metadata
     */
    public function getAllMetadata(?string $provider, ?string $segment, ?string $collection, ?string $endpoint): array
    {
        if (! $this->hasProvider($provider)) {
            return [];
        }

        if (! $this->hasCollection($provider, $segment, $collection)) {
            return [];
        }

        if (! $this->hasEndpoint($provider, $segment, $collection, $endpoint)) {
            return [];
        }

        return $this->getEndpoint($provider, $segment, $collection, $endpoint)['metadata'] ?? [];
    }

    /**
     * Get a specific metadata value for a given key from a provider, segment, collection, and endpoint.
     * e.g.: getMetadata('my_custom_api', 'v1', 'users', 'list',
     * 
     * @param string $provider Name of the API provider
     * @param string $segment Name of the API segment
     * @param string $collection Name of the collection
     * @param string $endpoint Name of the endpoint
     * @param string $key Metadata key to retrieve
     * @return mixed Metadata value, or null if the key does not exist
     */
    public function getMetadata(?string $provider, ?string $segment, ?string $collection, ?string $endpoint, string $key): mixed
    {
        return $this->getAllMetadata($provider, $segment, $collection, $endpoint)[$key] ?? null;
    }

    
    // ──────────────────────────────
    // DEBUG
    // ──────────────────────────────

    public function isDebugEnabled(?string $provider): bool
    {
        if (! $this->hasProvider($provider)) {
            return false;
        }

        $providerOptions = $this->getProvider($provider);
        return $providerOptions['debug']['enabled'] ?? false;
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
    // public function getResponseHeaders(string $providerName): array
    // {
    //     return $this->configuration[$providerName]['response']['headers'] ?? [];
    // }

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
    // public function getResponseCacheControl(string $providerName): string
    // {
    //     $isPublic       = $this->isResponseCachePublic($providerName);
    //     $noStore        = $this->getResponseCacheControlNoStore($providerName);
    //     $mustRevalidate = $this->getResponseCacheControlMustRevalidate($providerName);
    //     $maxAge         = $this->getResponseCacheControlMaxAge($providerName);

    //     $directives     = [];
    //     $directives[]   = $isPublic ? 'public' : 'private';
    //     $directives[]   = $noStore ? 'no-store' : null;
    //     $directives[]   = $mustRevalidate ? 'must-revalidate' : '';
    //     $directives[]   = $maxAge ? 'max-age=' . (int) $maxAge : '';

    //     $directives = array_filter($directives, static fn($v) => !empty(trim($v)));

    //     return implode(', ', $directives);
    // }





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
    public function isDocumentationEnabled(?string $provider): bool
    {
        return $this->configuration[$provider]['documentation']['enabled'] ?? false;
    }


    // ──────────────────────────────
    // Utils
    // ──────────────────────────────


    // public function getEntityRepository(string $entity)
    // {
    //     return $this->doctrine->getRepository($entity);
    // }

}