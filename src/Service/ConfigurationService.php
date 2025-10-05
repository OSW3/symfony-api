<?php 
namespace OSW3\Api\Service;

use OSW3\Api\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigurationService
{
    private readonly array $configuration;

    public function __construct(
        #[Autowire(service: 'service_container')] private ContainerInterface $container,
    ){
        $this->configuration = $container->getParameter(Configuration::NAME);
    }


    // ──────────────────────────────
    // Providers
    // ──────────────────────────────

    /**
     * Returns the configuration array for a specific API provider.
     *
     * @param string $providerName The name of the API provider (e.g., 'my_custom_api_v1').
     * @return array|null Returns the provider configuration array if found, or null if the provider does not exist.
     *
     * @example
     * $provider = $configurationService->getProvider('my_custom_api_v1'); 
     */
    public function getProvider(string $providerName): ?array
    {
        return $this->configuration[$providerName] ?? null;
    }

    /**
     * Returns the configuration for all API providers.
     * 
     * @return array An associative array of all providers. Each key is the provider name and each value is its configuration array.
     *
     * @example
     * $providers = $configurationService->getAllProviders();
     */
    public function getAllProviders(): array
    {
        return $this->configuration;
    }


    // ──────────────────────────────
    // Versioning
    // ──────────────────────────────

    /**
     * Returns the version of a specific API provider.
     *
     * @param string $providerName The name of the API provider (e.g., 'my_custom_api_v1').
     * @return string|null The version string (e.g., 'v1') if defined, or null if the provider does not exist or the version is not set.
     *
     * @example
     * $version = $configurationService->getVersion('my_custom_api_v1'); // returns 'v1'
     */
    public function getVersion(string $providerName): ?string
    {
        return $this->configuration[$providerName]['version'] ?? null;
    }


    // ──────────────────────────────
    // Route (global, collection, endpoint)
    // ──────────────────────────────
    
    /**
     * Get the route name pattern with optional fallback at endpoint, collection or provider level.
     *
     * Priority:
     *  1. Endpoint-specific pattern (if $endpointName is provided)
     *  2. Collection-level pattern
     *  3. Provider-level default pattern
     *
     * @param string      $providerName Name of the API provider
     * @param string|null $entityClass  Fully-qualified entity class name (optional)
     * @param string|null $endpointName Specific endpoint name (optional)
     * @return string Route name pattern (may include placeholders like {version}, {collection}, {action})
     *
     * @example
     * $pattern = $configService->getRouteNamePattern('my_api', App\Entity\Book::class, 'list');
     */
    public function getRouteNamePattern(string $providerName, ?string $entityClass = null, ?string $endpointName = null): string
    {
        // 1. Endpoint-specific pattern
        if ($entityClass && $endpointName) {
            $endpoint = $this->configuration[$providerName]['collections'][$entityClass]['endpoints'][$endpointName] ?? null;
            if ($endpoint && isset($endpoint['route']['name'])) {
                return $endpoint['route']['name'];
            }
        }

        // 2. Collection-level pattern
        if ($entityClass) {
            $collection = $this->configuration[$providerName]['collections'][$entityClass] ?? null;
            if ($collection && isset($collection['route']['name'])) {
                return $collection['route']['name'];
            }
        }

        // 3. Global default pattern
        return $this->configuration[$providerName]['routes']['name'] ?? '';
    }

    /**
     * Get the route prefix with optional fallback at endpoint, collection or provider level.
     *
     * Priority:
     *  1. Endpoint-specific pattern (if $endpointName is provided)
     *  2. Collection-level pattern
     *  3. Provider-level default pattern
     *
     * @param string      $providerName Name of the API provider
     * @param string|null $entityClass  Fully-qualified entity class name (optional)
     * @param string|null $endpointName Specific endpoint name (optional)
     * @return string Route prefix
     *
     * @example
     * $prefix = $configService->getRouteNamePattern('my_api', App\Entity\Book::class, 'list');
     */
    public function getRoutePrefix(string $providerName, ?string $entityClass = null, ?string $endpointName = null): string
    {
        // 1. Endpoint-specific prefix
        if ($entityClass && $endpointName) {
            $endpoint = $this->configuration[$providerName]['collections'][$entityClass]['endpoints'][$endpointName] ?? null;
            if ($endpoint && isset($endpoint['route']['prefix'])) {
                return $endpoint['route']['prefix'];
            }
        }

        // 2. Collection-level prefix
        if ($entityClass) {
            $collection = $this->configuration[$providerName]['collections'][$entityClass] ?? null;
            if ($collection && isset($collection['route']['prefix'])) {
                return $collection['route']['prefix'];
            }
        }

        // 3. Global default prefix
        return $this->configuration[$providerName]['routes']['prefix'] ?? '';
    }


    // ──────────────────────────────
    // Search 
    // ──────────────────────────────

    public function isSearchEnabled(string $providerName, ?string $entityClass = null): bool
    {
        $collection = $entityClass ? $this->getCollection($providerName, $entityClass) : null;
        if ($collection && isset($collection['search']['enabled'])) {
            return $collection['search']['enabled'];
        }

        return $this->configuration[$providerName]['search'] ?? false;
    }

    public function getSearchFields(string $providerName, string $entityClass): array
    {
        return $this->configuration[$providerName]['collections'][$entityClass]['search']['fields'] ?? [];
    }


    // ──────────────────────────────
    // Pagination
    // ──────────────────────────────

    /**
     * Check if pagination is globally enabled for the given API provider.
     *
     * @param string $providerName Name of the API provider
     * @return bool True if pagination is enabled, false otherwise
     *
     * @example
     * $enabled = $configService->isPaginationEnabled('my_custom_api_v1');
     */
    public function isPaginationEnabled(string $providerName): bool
    {
        return $this->configuration[$providerName]['pagination']['enable'] ?? false;
    }

    public function getPaginationPerPage(string $providerName, ?string $entityClass = null): int
    {
        $collection = $entityClass ? $this->getCollection($providerName, $entityClass) : null;
        if ($collection && isset($collection['pagination'])) {
            return $collection['pagination'];
        }

        return $this->configuration[$providerName]['pagination']['per_page'] ?? 10;
    }

    /**
     * Get the maximum number of items allowed per page for pagination.
     *
     * @param string $providerName Name of the API provider
     * @return int Maximum items per page, defaults to 100 if not defined
     *
     * @example
     * $maxPerPage = $configService->getPaginationMaxPerPage('my_custom_api_v1');
     */
    public function getPaginationMaxPerPage(string $providerName): int
    {
        return $this->configuration[$providerName]['pagination']['max_per_page'] ?? 100;
    }


    // ──────────────────────────────
    // URL response settings
    // ──────────────────────────────

    /**
     * Check if URL elements are included in API responses.
     *
     * @param string $providerName Name of the API provider
     * @return bool True if URLs are included, false otherwise
     *
     * @example
     * $supported = $configService->hasUrlSupport('my_custom_api_v1');
     */
    public function hasUrlSupport(string $providerName): bool
    {
        return $this->configuration[$providerName]['url']['support'] ?? false;
    }

    /**
     * Check if URLs in API responses should be absolute.
     *
     * @param string $providerName Name of the API provider
     * @return bool True for absolute URLs, false for relative URLs
     *
     * @example
     * $absolute = $configService->isUrlAbsolute('my_custom_api_v1');
     */
    public function isUrlAbsolute(string $providerName): bool
    {
        return $this->configuration[$providerName]['url']['absolute'] ?? false;
    }


    // ──────────────────────────────
    // Collections
    // ──────────────────────────────

    /**
     * Returns all collections defined for a specific provider.
     * dump( $this->configuration->getCollections('my_custom_api_v1') );
     *
     * @param string $providerName
     * @return array|null Returns an array of collections if the provider exists, null otherwise.
     */
    public function getCollections(string $providerName): array
    {
        return $this->configuration[$providerName]['collections'] ?? [];
    }

    /**
     * Returns a specific collection configuration.
     * dump( $this->configuration->getCollection('my_custom_api_v1', 'App\Entity\Book') );
     *
     * @param string $providerName
     * @param string $entityClass Fully qualified class name of the entity (e.g., 'App\Entity\Book')
     * @return array|null Collection configuration array or null if not found.
     */
    public function getCollection(string $providerName, string $entityClass): ?array
    {
        return $this->configuration[$providerName]['collections'][$entityClass] ?? null;
    }

    // ──────────────────────────────
    // Endpoints
    // ──────────────────────────────

    public function getEndpoints(string $providerName, string $entityClass): array
    {
        return $this->getCollection($providerName, $entityClass)['endpoints'] ?? [];
    }

    public function getEndpoint(string $providerName, string $entityClass, string $endpointName): ?array
    {
        return $this->getCollection($providerName, $entityClass)['endpoints'][$endpointName] ?? null;
    }

    
    // ──────────────────────────────
    // Utility for endpoints
    // ──────────────────────────────
    
    public function getEndpointRoute(string $providerName, string $entityClass, string $endpointName): ?array
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['route'] ?? null;
    }

    public function getEndpointRouteName(string $providerName, string $entityClass, string $endpointName): string
    {
        return $this->getRouteNamePattern($providerName, $entityClass, $endpointName);;
    }

    public function getEndpointRouteMethods(string $providerName, string $entityClass, string $endpointName): array
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['route']['methods'] ?? [];
    }

    public function getEndpointRouteController(string $providerName, string $entityClass, string $endpointName): string
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['route']['controller'] ?? '';
    }

    public function getEndpointRouteOptions(string $providerName, string $entityClass, string $endpointName): array
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['route']['options'] ?? [];
    }

    public function getEndpointRouteCondition(string $providerName, string $entityClass, string $endpointName): string
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['route']['condition'] ?? '';
    }

    public function getEndpointRouteRequirements(string $providerName, string $entityClass, string $endpointName): array
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['route']['requirements'] ?? [];
    }


    // ──────────────────────────────
    // Endpoints Repository
    // ──────────────────────────────

    public function getRepositoryClass(string $providerName, string $entityClass, string $endpointName): ?array
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['repository']['service'] ?? null;
    }

    public function getMethod(string $providerName, string $entityClass, string $endpointName): string
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['repository']['method'] ?? '';
    }

    public function getCriteria(string $providerName, string $entityClass, string $endpointName): array
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['repository']['criteria'] ?? [];
    }

    public function getOrderBy(string $providerName, string $entityClass, string $endpointName): array
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['repository']['order_by'] ?? [];
    }

    public function getLimit(string $providerName, string $entityClass, string $endpointName): string
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['repository']['limit'] ?? '';
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


    // ──────────────────────────────
    // Endpoints Granted
    // ──────────────────────────────

    public function getGranted(string $providerName, string $entityClass, string $endpointName): array
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['granted']['roles'] ?? [];
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

    public function getSerializeGroups(string $providerName, string $entityClass, string $endpointName): array
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['serialization']['groups'] ?? [];
    }

    public function getSerializeTransformer(string $providerName, string $entityClass, string $endpointName): string
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['serialization']['transformer'] ?? '';
    }


    // ──────────────────────────────
    // Endpoints Transformer
    // ──────────────────────────────

    public function getTransformer(string $providerName, string $entityClass, string $endpointName): string
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['transformer'] ?? '';
    }


    // ──────────────────────────────
    // Endpoints Rate Limit
    // ──────────────────────────────

    public function getRateLimit(string $providerName, string $entityClass, string $endpointName): string
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['rate_limit']['global'] ?? '';
    }

    public function getRateLimitByRole(string $providerName, string $entityClass, string $endpointName): array
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['rate_limit']['by_role'] ?? [];
    }

    public function getRateLimitByUser(string $providerName, string $entityClass, string $endpointName): array
    {
        return $this->getEndpoint($providerName, $entityClass, $endpointName)['rate_limit']['by_user'] ?? [];
    }
}