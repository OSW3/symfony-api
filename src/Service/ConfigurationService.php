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
        #[Autowire(service: 'service_container')] private readonly ContainerInterface $container,
        private readonly KernelInterface $kernel,
        private readonly ManagerRegistry $doctrine,
        private readonly RequestStack $requestStack,
    ){
        $this->configuration = $container->getParameter(Configuration::NAME);
        $this->request = $this->requestStack->getCurrentRequest();
    }


    // ──────────────────────────────
    // Context
    // ──────────────────────────────

    /**
     * Guess the provider name by the current route name
     */
    public function guessProvider(): ?string
    {
        return $this->findRouteMapping()['provider'] ?? null;
    }

    /**
     * Guess the collection name by the current route name
     */
    public function guessCollection(): ?string
    {
        return $this->findRouteMapping()['collection'] ?? null;
    }

    /**
     * Guess the endpoint name by the current route name
     */
    public function guessEndpoint(): ?string
    {
        return $this->findRouteMapping()['endpoint'] ?? null;
    }

    /**
     * Get provider, collection and endpoint by current route name
     */
    private function findRouteMapping(): array|null
    {
        $route = $this->request->get('_route');

        if (!$route) {
            return null;
        }

        foreach ($this->getAllProviders() as $providerName => $provider) {
            foreach ($provider['collections'] ?? [] as $collectionName => $entityOptions) {
                foreach ($entityOptions['endpoints'] ?? [] as $endpointName => $endpointOption) {
                    if (($endpointOption['route']['name'] ?? null) === $route) {
                        return [
                            'provider'   => $providerName,
                            'collection' => $collectionName,
                            'endpoint'   => $endpointName,
                        ];
                    }
                }
            }
        }

        return null;
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

    public function isValidProvider(string $provider): bool
    {
        return array_key_exists($provider, $this->getAllProviders());
    }

    // ──────────────────────────────
    // Versioning
    // ──────────────────────────────

    /**
     * Get version number for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return int Version number
     */
    public function getVersionNumber(string $providerName): int
    {
        return $this->configuration[$providerName]['version']['number'];
    }

    /**
     * Get version prefix for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Version prefix (e.g., 'v')
     */
    public function getVersionPrefix(string $providerName): string
    {
        return $this->configuration[$providerName]['version']['prefix'];
    }

    /**
     * Get version location for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Version location (e.g., 'header', 'url')
     */
    public function getVersionLocation(string $providerName): string
    {
        return $this->configuration[$providerName]['version']['location'];
    }

    /**
     * Get version header format for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Version header format (e.g., 'application/vnd.{vendor}.v{version}+json')
     */
    public function getVersionHeaderFormat(string $providerName): string
    {
        return $this->configuration[$providerName]['version']['header_format'] ?? '';
    }

    /**
     * Check if a specific API provider is marked as deprecated.
     * 
     * @param string $providerName Name of the API provider
     * @return bool True if the provider is deprecated, false otherwise
     */
    public function isDeprecated(string $providerName): bool
    {
        return $this->configuration[$providerName]['version']['deprecated'] ?? false;
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

    public function getHosts(string $providerName): array
    {
        return $this->configuration[$providerName]['routes']['hosts'] ?? [];
    }

    public function getSchemes(string $providerName): array
    {
        return $this->configuration[$providerName]['routes']['schemes'] ?? [];
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
    // Debug 
    // ──────────────────────────────

    public function isDebugEnabled(string $providerName): bool
    {
        return $this->configuration[$providerName]['debug']['enable'] ?? false;
    }

    
    // ──────────────────────────────
    // Tracing 
    // ──────────────────────────────

    public function isTracingEnabled(string $providerName): bool
    {
        return $this->configuration[$providerName]['tracing']['enable'] ?? false;
    }

    public function isTracingIdRequestEnabled(string $providerName): bool
    {
        return $this->configuration[$providerName]['tracing']['request'] ?? false;
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

    public function getPaginationLimit(string $providerName, ?string $entityClass = null): int
    {
        $collection = $entityClass ? $this->getCollection($providerName, $entityClass) : null;
        if ($collection && isset($collection['pagination'])) {
            return $collection['pagination'];
        }

        return $this->configuration[$providerName]['pagination']['limit'] ?? 10;
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
    public function getPaginationMaxLimit(string $providerName): int
    {
        return $this->configuration[$providerName]['pagination']['max_limit'] ?? 100;
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

    public function getUrlProperty(string $providerName): string
    {
        return $this->configuration[$providerName]['url']['property'] ?? 'url';
    }


    // ──────────────────────────────
    // Response
    // ──────────────────────────────

    /**
     * Get the response template path for a specific API provider.
     * 
     * @param string $providerName Name of the API provider
     * @return string Path to the response template file
     */
    public function getResponseTemplate(string $providerName): string
    {
        return $this->configuration[$providerName]['response']['template'] ?? 'Resources/templates/response.yaml';
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
        $isPublic       = $this->getResponseCacheControlIsPublic($providerName);
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
    public function getResponseCacheControlIsPublic(string $providerName): bool
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

    /**
     * Get the hashing algorithm used for generating response hashes.
     *
     * @param string $providerName Name of the API provider
     * @return string Hashing algorithm (e.g., 'md5', 'sha256')
     *
     * @example
     * $algorithm = $configService->getResponseHashAlgorithm('my_custom_api_v1'); // returns 'md5'
     */
    public function getResponseHashAlgorithm(string $providerName): string
    {
        return $this->configuration[$providerName]['response']['algorithm'] ?? 'md5';
    }


    // ──────────────────────────────
    // Rate Limit
    // ──────────────────────────────

    public function isRateLimitEnabled(string $providerName): bool
    {
        return $this->configuration[$providerName]['rate_limit']['enable'] ?? false;
    }

    public function getRateLimit(string $providerName): int
    {
        return $this->configuration[$providerName]['rate_limit']['limit'] ?? 1000;
    }

    // public function getRateLimit(string $providerName, string $entityClass, string $endpointName): string
    // {
    //     return $this->getEndpoint($providerName, $entityClass, $endpointName)['rate_limit']['global'] ?? '';
    // }

    // public function getRateLimitByRole(string $providerName, string $entityClass, string $endpointName): array
    // {
    //     return $this->getEndpoint($providerName, $entityClass, $endpointName)['rate_limit']['by_role'] ?? [];
    // }

    // public function getRateLimitByUser(string $providerName, string $entityClass, string $endpointName): array
    // {
    //     return $this->getEndpoint($providerName, $entityClass, $endpointName)['rate_limit']['by_user'] ?? [];
    // }

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
    // Documentation
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
    // Utils
    // ──────────────────────────────


    public function getEntityRepository(string $entity)
    {
        return $this->doctrine->getRepository($entity);
    }

}