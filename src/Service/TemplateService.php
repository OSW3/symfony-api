<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\AppService;
use Symfony\Component\Yaml\Yaml;
use OSW3\Api\Service\DebugService;
use OSW3\Api\Service\ClientService;
use OSW3\Api\Service\ServerService;
use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\VersionService;
use OSW3\Api\Service\SecurityService;
use OSW3\Api\Service\RateLimitService;
use Symfony\Component\Filesystem\Path;
use OSW3\Api\Service\PaginationService;
use OSW3\Api\Service\ConfigurationService;
use OSW3\Api\Service\DocumentationService;
use OSW3\Api\Service\ExecutionTimeService;
use OSW3\Api\Service\ResponseStatusService;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class TemplateService 
{
    private array $data = [];

    public function __construct(
        private readonly AppService $app,
        private readonly DebugService $debug,
        private readonly ServerService $server,
        private readonly ClientService $client,
        private readonly VersionService $version,
        private readonly RequestService $request,
        private readonly KernelInterface $kernel,
        private readonly SecurityService $security,
        private readonly RateLimitService $rateLimit,
        private readonly ExecutionTimeService $timer,
        private readonly ResponseStatusService $status,
        private readonly PaginationService $pagination,
        private readonly ConfigurationService $configuration,
        private readonly DocumentationService $documentation,
        private readonly ResponseService $responseService,
        private readonly RouteService $routeService,
        private readonly ContextService $contextService,
        private readonly ChecksumService $checksumService,
        #[Autowire(service: 'service_container')] private readonly ContainerInterface $container,
    ){}

    /**
     * Retrieves the template content for the given type.
     * 
     * @param string $type The template type (e.g., 'list', 'item', 'error', 'no_content').
     * @return array The template content.
     * @throws \Exception If the template cannot be found or loaded.
     */
    public function getTemplate(string $type): array
    {
        // Resolve the template path
        $path = $this->resolvePath($type);

        // Load the template source
        $source = $this->load($path);

        return $source;
    }

    /**
     * Resolves the absolute path to the template file based on provider and type.
     * The resolution follows this order:
     * 1. Checks for an absolute path defined for the provider. e.g.: /templates/my_template.yaml
     * 2. Checks for a local override in the root directory. e.g.: rootdir + provider relative path.
     * 3. Checks for standard project templates in the root directory. e.g.: rootdir/templates + provider relative path.
     * 4. Falls back to the default template in the bundle directory. e.g.: Bundle directory + default relative path.
     * 
     * @param string $provider The provider name.
     * @param string $type The template type (e.g., 'list', 'item', 'error', 'no_content').
     * @return string The absolute path to the template file.
     * @throws \Exception If the template type is unknown or the file does not exist.
     */
    public function resolvePath(string $type): string 
    {
        // Current context
        $currentRoute = $this->routeService->getCurrentRoute();
        $context      = $currentRoute ? $currentRoute['options']['context'] : [];
        // $context = $this->configuration->getContext();
        $provider = $context['provider'] ?? null;

        if (!$provider) {
            // return '';
            throw new \Exception("No provider defined in context");
        }

        // Resolve the path source
        $rootDir = $this->kernel->getProjectDir();
        $bundleDir = $this->kernel->getBundle('ApiBundle')->getPath();

        // Resolve the template path based on type
        $templatePath = match($type) {
            'list'       => $this->configuration->getListTemplate($provider),
            'item'       => $this->configuration->getItemTemplate($provider),
            'delete'     => $this->configuration->getDeleteTemplate($provider),
            'error'      => $this->configuration->getErrorTemplate($provider),
            'no_content' => $this->configuration->getNotFoundTemplate($provider),
            default      => throw new \Exception("Unknown template type"),
        };

        // Candidate paths to check
        $candidates = [
            Path::join("/", $templatePath),
            Path::join($rootDir, 'templates', $templatePath),
            Path::join($rootDir, $templatePath),
            Path::join($bundleDir, $templatePath),
        ];

        // Resolve first existing file
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        // If no template was found, throw an exception
        throw new \Exception(sprintf(
            "Template not found for type '%s' (provider '%s'). Tried:\n%s",
            $type,
            $provider,
            implode("\n", array_map(fn($c) => " - $c", $candidates))
        ));
    }

    /**
     * Loads the template file based on its format.
     * Supports JSON, PHP, XML, and YAML formats.
     * 
     * @param string $path The absolute path to the template file.
     * @throws \Exception If the file does not exist or the format is unsupported.
     */
    public function load(string $path) 
    {
        if (!file_exists($path)) {
            // return [];
            throw new \Exception("Template file does not exist: $path");
        }

        return match (pathinfo($path, PATHINFO_EXTENSION)) {
            // JSON
            'json'  => json_decode(file_get_contents($path), true) ?? [],

            // PHP
            'php'   => include $path,

            // XML
            'xml'   => json_decode(json_encode(simplexml_load_string(file_get_contents($path))), true) ?? [],

            // YAML
            'yml', 'yaml'  => Yaml::parseFile($path) ?? [],

            // Default
            default => throw new \Exception("Unsupported template format: $path"),
        };
    }

    public function parse(array $template): array
    {
        // Current context
        // $context    = $this->routeService->getContext();
        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();

        array_walk_recursive($template, function (&$value, $k) use ($provider, $collection, $endpoint) {
            
            if (!is_string($value)) return;

            // Check if the value is a callable
            // --

            if ($this->isCallable($value)) {
                $value = $this->callMethod($value);
                return;
            }


            // Parse the expression
            // --

            $expression = null;
            $default    = null;

            if (preg_match('/\{([^,}]+)(?:,\s*([^\}]+))?\}/', $value, $matches)) {
                $expression = trim($matches[1]);
                $default    = isset($matches[2]) ? trim($matches[2]) : null;
            }

            $data      = $this->responseService->getData();
            $algorithm = $this->configuration->getChecksumAlgorithm($provider);


            // App
            $value = match($expression) {
                'app.name'        => $this->app->getName(),
                'app.vendor'      => $this->app->getVendor(),
                'app.version'     => $this->app->getVersion(),
                'app.description' => $this->app->getDescription(),
                'app.license'     => $this->app->getLicense(),
                default           => $default ?? $value,
            };

            // Context
            $value = match($expression) {
                'context.provider'   => $provider,
                'context.collection' => $collection,
                'context.endpoint'   => $endpoint,
                default              => $default ?? $value,
            };

            // Version
            $value = match($expression) {
                'version.label'         => $this->version->getLabel(),
                'version.number'        => $this->version->getNumber(),
                'version.prefix'        => $this->version->getPrefix(),
                'version.all'           => $this->version->getAllVersions(),
                'version.supported'     => $this->version->getSupportedVersions(),
                'version.deprecated'    => $this->version->getDeprecatedVersions(),
                'version.is_deprecated' => $this->version->isDeprecated(),
                'version.is_beta'       => $this->version->isBeta(),
                default                 => $default ?? $value,
            };

            // Documentation
            $value = match($expression) {
                'documentation.url' => $this->documentation->getUrl($provider),
                default             => $default ?? $value,
            };

            // Status
            $value = match($expression) {
                'status.code'       => $this->status->getCode(),
                'status.text'       => $this->status->getText(),
                'status.state'      => $this->status->getState(),
                'status.is_success' => $this->status->isSuccess(),
                'status.is_failed'  => $this->status->isFailed(),
                'status.is_error'   => $this->status->isError(),
                default             => $default ?? $value,
            };

            // Request
            $value = match($expression) {
                'request.method'    => $this->request->getMethod(),
                'request.scheme'    => $this->request->getScheme(),
                'request.is_secure' => $this->request->isSecure(),
                'request.base'      => $this->request->getBase(),
                'request.port'      => $this->request->getPort(),
                'request.uri'       => $this->request->getUri(),
                'request.path'      => $this->request->getPath(),
                'request.params'    => $this->request->getQueryParams(),
                'request.locale'    => $this->request->getLocale(),
                default             => $default ?? $value,
            };

            // Client
            $value = match($expression) {
                'client.ip'                    => $this->client->getIp(),
                'client.user_agent'            => $this->client->getUserAgent(),
                'client.device'                => $this->client->getDevice(),
                'client.is_mobile'             => $this->client->isMobile(),
                'client.is_tablet'             => $this->client->isTablet(),
                'client.is_desktop'            => $this->client->isDesktop(),
                'client.browser'               => $this->client->getBrowser(),
                'client.browser_version'       => $this->client->getBrowserVersion(),
                'client.browser_version_major' => $this->client->getBrowserVersionMajor(),
                'client.browser_version_minor' => $this->client->getBrowserVersionMinor(),
                'client.browser_version_patch' => $this->client->getBrowserVersionPatch(),
                'client.os'                    => $this->client->getOs(),
                'client.os_version'            => $this->client->getOsVersion(),
                'client.os_version_major'      => $this->client->getOsVersionMajor(),
                'client.os_version_minor'      => $this->client->getOsVersionMinor(),
                'client.os_version_patch'      => $this->client->getOsVersionPatch(),
                'client.engine'                => $this->client->getEngine(),
                'client.languages'             => $this->client->getLanguages(),
                'client.language'              => $this->client->getLanguage(),
                'client.fingerprint'           => $this->client->getFingerprint(),
                default                        => $default ?? $value,
            };

            // Server
            $value = match($expression) {
                'server.ip'              => $this->server->getIp(),
                'server.host'            => $this->server->getHostname(),
                'server.env'             => $this->server->getEnvironment(),
                'server.php_version'     => $this->server->getPhpVersion(),
                'server.symfony_version' => $this->server->getSymfonyVersion(),
                'server.name'            => $this->server->getName(),
                'server.software'        => $this->server->getSoftware(),
                'server.software_name'   => $this->server->getSoftwareName(),
                'server.software_version'=> $this->server->getSoftwareVersion(),
                'server.software_release'=> $this->server->getSoftwareRelease(),
                'server.os'              => $this->server->getOs(),
                'server.os_version'      => $this->server->getOsVersion(),
                'server.os_release'      => $this->server->getOsRelease(),
                'server.date'            => $this->server->getDate(),
                'server.time'            => $this->server->getTime(),
                'server.timezone'        => $this->server->getTimezone(),
                'server.region'          => $this->server->getRegion(),
                default                  => $default ?? $value,
            };

            // Pagination
            // if ($this->pagination->isEnabled($provider))
            {
                $value = match($expression) {
                    'pagination.pages'    => $this->pagination->getTotalPages(),
                    'pagination.page'     => $this->pagination->getPage(),
                    'pagination.total'    => $this->pagination->getTotal(),
                    'pagination.limit'    => $this->pagination->getLimit(),
                    'pagination.offset'   => $this->pagination->getOffset(),
                    'pagination.prev'     => $this->pagination->getPrevious(),
                    'pagination.next'     => $this->pagination->getNext(),
                    'pagination.self'     => $this->pagination->getSelf(),
                    'pagination.first'    => $this->pagination->getFirst(),
                    'pagination.last'     => $this->pagination->getLast(),
                    'pagination.is_first' => $this->pagination->isFirstPage(),
                    'pagination.is_last'  => $this->pagination->isLastPage(),
                    'pagination.has_prev' => $this->pagination->hasPreviousPage(),
                    'pagination.has_next' => $this->pagination->hasNextPage(),
                    default               => $default ?? $value,
                };
            }

            // User
            $value = match($expression) {
                'user.is_authenticated' => $this->security->isAuthenticated(),
                'user.id'               => $this->security->getId(),
                'user.username'         => $this->security->getUserName(),
                'user.roles'            => $this->security->getRoles(),
                'user.email'            => $this->security->getEmail(),
                'user.permissions'      => $this->security->getPermissions(),
                'user.mfa_enabled'      => $this->security->isMfaEnabled(),
                default                 => $default ?? $value,
            };

            // Response
            $value = match($expression) {
                'response.timestamp'          => gmdate('c'),
                'response.data'               => $data,
                'response.count'              => $this->responseService->getCount(),
                'response.size'               => $this->responseService->getSize(),
                'response.hash'               => $this->responseService->computeHash($algorithm),
                'response.hash_md5'           => $this->responseService->computeHash('md5'),
                'response.hash_sha1'          => $this->responseService->computeHash('sha1'),
                'response.hash_sha256'        => $this->responseService->computeHash('sha256'),
                'response.hash_sha512'        => $this->responseService->computeHash('sha512'),

                'response.is_compressed'      => $this->responseService->isCompressed(),
                'response.compression_format' => $this->responseService->getCompressionFormat(),
                'response.compression_level'  => $this->responseService->getCompressionLevel(),

                'response.etag'          => null,
                'response.validated'     => null,
                'response.signature'     => null,
                'response.cache_key'     => null,
                'response.cors'          => null,
                
                'response.error.code'    => null,
                'response.error.message' => null,
                'response.error.details' => null,
                'response.error.doc_url' => null,
                default                  => $default ?? $value,
            };

            // Metadata
            $value = match($expression) {
                'meta.description'      => $this->configuration->getMetadata(
                    provider  : $provider,
                    collection: $collection,
                    endpoint  : $endpoint,
                    key       : 'description'
                ) ?? $default,
                // 'meta.deprecated'       => $this->configuration->getMetadataDeprecated($provider, $collection, $endpoint) ?? $default,
                // 'meta.cache_ttl'        => $this->configuration->getMetadataCacheTTL($provider, $collection, $endpoint) ?? $default,
                // 'meta.tags'             => $this->configuration->getMetadataTags($provider, $collection, $endpoint) ?? $default,
                // 'meta.operation_id'     => $this->configuration->getMetadataOperationId($provider, $collection, $endpoint) ?? $default,
                'meta.license'          => null,
                'meta.author'           => null,
                'meta.contact'          => null,
                'meta.category'         => null,
                'meta.visibility'       => null,
                'meta.links'            => null,
                'meta.example_request'  => null,
                'meta.example_response' => null,
                default                 => $default ?? $value,
            };

            // Rate Limit
            if (!$this->configuration->isRateLimitEnabled($provider)) {
                $value = match($expression) {
                    'rate_limit.limit'     => $this->rateLimit->getLimit($provider),
                    'rate_limit.remaining' => $this->rateLimit->getRemaining($provider),
                    'rate_limit.reset'     => $this->rateLimit->getReset($provider),
                    default                => $default ?? $value,
                };
            }

            // Debug
            if ($this->configuration->isDebugEnabled($provider))
            {
                $value = match($expression) {
                    'debug.memory'               => $this->debug->getMemoryUsage(),
                    'debug.peak_memory'          => $this->debug->getMemoryPeak(),
                    'debug.execution_time'       => $this->timer->getDuration(),
                    'debug.execution_time_unit'  => $this->timer->getUnit(),
                    'debug.log_level'            => $this->debug->getLogLevel(),
                    'debug.count_included_files' => $this->debug->getCountIncludedFiles(),
                    'debug.included_files'       => $this->debug->getIncludedFiles(),
                    // 'debug.queue_time'           => $this->debug->getQueueTime(),
                    default                      => $default ?? $value,
                };
            }
        });

        return $template;
    }



    // ──────────────────────────────
    // Callable
    // ──────────────────────────────

    public function isCallable(string $callableString) : bool
    {
        if (strpos($callableString, '::') === false) {
            return false;
        }

        [$class, $method] = explode('::', $callableString, 2);

        if (!class_exists($class)) {
            return false;
        }

        // $instance = new $class();
        $instance = $this->container->get($class);

        if (!method_exists($instance, $method)) {
            return false;
        }

        if (!is_callable([$instance, $method])) {
            return false;
        }

        return true;
    }

    public function callMethod(string $callableString) 
    {
        if ($this->isCallable($callableString) === false) {
            return null;
        }

        [$class, $method] = explode('::', $callableString, 2);
        // $instance = new $class();
        $instance = $this->container->get($class);
        $result           = $instance->$method();

        return $result;
    }
}