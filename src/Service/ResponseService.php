<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ApiService;
use OSW3\Api\Service\AppService;
use Symfony\Component\Yaml\Yaml;
use OSW3\Api\Service\ClientService;
use OSW3\Api\Service\RequestService;
use Symfony\Component\Filesystem\Path;
use OSW3\Api\HttpFoundation\XmlResponse;
use OSW3\Api\Service\ConfigurationService;
use OSW3\Api\Service\DocumentationService;
use OSW3\Api\Service\ResponseStatusService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ResponseService 
{
    public function __construct(
        private readonly ApiService $api,
        private readonly AppService $app,
        private readonly DebugService $debug,
        private readonly ClientService $client,
        private readonly ServerService $server,
        private readonly HeaderService $headers,
        private readonly KernelInterface $kernel,
        private readonly RequestService $request,
        private readonly SecurityService $security,
        private readonly RateLimitService $rateLimit,
        private readonly PaginationService $pagination,
        private readonly ResponseStatusService $status,
        private readonly ConfigurationService $configuration,
        private readonly DocumentationService $documentation,

        #[Autowire(service: 'service_container')] private readonly ContainerInterface $container,
    ){}

    private function getContext(): array 
    {
        return [
            'provider'   => $this->configuration->guessProvider(),
            'collection' => $this->configuration->guessCollection(),
            'endpoint'   => $this->configuration->guessEndpoint(),
        ];
    }


    // ──────────────────────────────
    // Builder
    // ──────────────────────────────

    public function buildJsonResponse(array $data, int $statusCode = 200): Response 
    {
        return new JsonResponse($data, $statusCode);
    }

    public function buildXmlResponse(array $data, int $statusCode = 200): Response
    {
        return XmlResponse::fromArray($data, $statusCode);
    }

    public function buildYamlResponse(array $data, int $statusCode = 200): Response 
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/x-yaml');
        $response->setContent(Yaml::dump($data, 4, 2, Yaml::DUMP_OBJECT_AS_MAP));
        $response->setStatusCode($statusCode);
        return $response;
    }





    public function create(array $data): Response
    {
        ['provider' => $provider, 'collection' => $collection,'endpoint' => $endpoint] = $this->getContext();

        $statusCode = $this->status->getCode();
        $payload    = $this->payload($data);


        // Build the response by format (with $payload and $statusCode)
        // --

        $response = match ($this->configuration->getResponseFormat($provider)) {
            'json'  => $this->buildJsonResponse($payload, $statusCode),
            'xml'   => $this->buildXmlResponse($payload, $statusCode),
            'yaml'  => $this->buildYamlResponse($payload, $statusCode),
            default => $this->buildJsonResponse($payload, $statusCode),
        };


        // Add and modify headers
        // --

        $this->headers->init($response->headers);

        if ($this->configuration->getVersionHeaderFormat($provider) === 'header') 
        {
            $this->headers->addApiVersion();
        }


        $this->headers->addCacheControl();


        // dump($response->headers->all());

        // foreach ($this->headers->all() as $key => $value) 
        // {
        //     $response->headers->set($key, $value);
        // }
        // dd($response->headers->all());


        // $response->headers->set('X-App-Version', '1.2.3');
        // $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        // $response->headers->set('Access-Control-Allow-Origin', '*');

        // $response->setContent(json_encode($payload));
        // $response->setStatusCode($statusCode);

        return $response;
    }

    public function payload(mixed $data)
    {
        ['provider' => $provider,'collection' => $collection,'endpoint' => $endpoint] = $this->getContext();

        // Resolve template path
        $path     = $this->getTemplatePath($provider);
        $template = $this->getTemplate($path);

        array_walk_recursive($template, function (&$value, $k) use ($data, $provider, $collection, $endpoint) {
            
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


            // App
            $value = match($expression) {
                'app.name'    => $this->app->getName(),
                'app.vendor'  => $this->app->getVendor(),
                'app.version' => $this->app->getVersion(),
                default       => $default ?? $value,
            };

            // API
            $value = match($expression) {
                'api.version'            => $this->api->getFullVersion(),
                'api.version.number'     => $this->api->getVersionNumber(),
                'api.version.prefix'     => $this->api->getVersionPrefix(),
                'api.supported_versions' => $this->api->getAllVersions(),
                'api.deprecated'         => $this->api->isDeprecated(),
                default                  => $default ?? $value,
            };

            // Documentation
            $value = match($expression) {
                'documentation.url' => $this->documentation->getUrl($provider),
                default             => $default ?? $value,
            };

            // Status
            $value = match($expression) {
                'status.code'  => $this->status->getCode(),
                'status.text'  => $this->status->getText(),
                'status.state' => $this->status->getState(),
                default        => $default ?? $value,
            };

            // Request
            $value = match($expression) {
                'request.scheme' => $this->request->getScheme(),
                'request.secure' => $this->request->isSecure(),
                'request.base'   => $this->request->getBase(),
                'request.port'   => $this->request->getPort(),
                'request.uri'    => $this->request->getUri(),
                'request.path'   => $this->request->getPath(),
                'request.query'  => $this->request->getQueryParams(),
                'request.method' => $this->request->getMethod(),
                'request.locale' => $this->request->getLocale(),
                default          => $default ?? $value,
            };

            // Client
            $value = match($expression) {
                'client.ip'              => $this->client->getIp(),
                'client.vpn_status'      => $this->client->getVpnStatus(),
                'client.user_agent'      => $this->client->getUserAgent(),
                'client.device'          => $this->client->getDevice(),
                'client.is_mobile'       => $this->client->isMobile(),
                'client.is_tablet'       => $this->client->isTablet(),
                'client.is_desktop'      => $this->client->isDesktop(),
                'client.browser'         => $this->client->getBrowser(),
                'client.browser_version' => $this->client->getBrowserVersion(),
                'client.os'              => $this->client->getOs(),
                'client.os_version'      => $this->client->getOsVersion(),
                'client.engine'          => $this->client->getEngine(),
                'client.languages'       => $this->client->getLanguages(),
                'client.language'        => $this->client->getLanguage(),
                default                  => $default ?? $value,
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
            if ($this->configuration->isPaginationEnabled($provider))
            {
                $value = match($expression) {
                    'pagination.page'     => $this->pagination->getPage() ?? 1,
                    'pagination.limit'    => $this->pagination->getLimit() ?? 25,
                    'pagination.total'    => $this->pagination->getTotal() ?? 0,
                    'pagination.pages'    => $this->pagination->getTotalPages() ?? 0,
                    'pagination.prev'     => $this->pagination->getPrev(),
                    'pagination.next'     => $this->pagination->getNext(),
                    'pagination.first'    => $this->pagination->getFirst(),
                    'pagination.last'     => $this->pagination->getLast(),
                    'pagination.is_first' => $this->pagination->isFirst(),
                    'pagination.is_last'  => $this->pagination->isLast(),
                    default               => $default ?? $value,
                };
            }

            // User
            $value = match($expression) {
                'user.id'               => $this->security->getId(),
                'user.username'         => $this->security->getUserName(),
                'user.roles'            => $this->security->getRoles(),
                'user.email'            => $this->security->getEmail(),
                'user.token_issued_at'  => $this->security->getTokenIssuedAt(),
                'user.token_expires_at' => $this->security->getTokenExpiresAt(),
                'user.token_scopes'     => $this->security->getTokenScopes(),
                'user.permissions'      => $this->security->getPermissions(),
                'user.mfa_enabled'      => $this->security->getMfaEnabled(),
                default                 => $default ?? $value,
            };

            // Context
            $value = match($expression) {
                'context.provider'   => $provider,
                'context.collection' => $collection,
                'context.endpoint'   => $endpoint,
                default              => $default ?? $value,
            };

            // Response
            $algorithm = $this->configuration->getResponseHashAlgorithm($provider);
            $value = match($expression) {
                'response.timestamp'   => gmdate('c'),
                'response.data'        => $data,
                'response.count'       => is_countable($data) ? count($data) : 1,
                'response.size'        => null,
                'response.hash'        => $this->computeHash($algorithm, $data),
                'response.hash_md5'    => $this->computeHash('md5', $data),
                'response.hash_sha1'   => $this->computeHash('sha1', $data),
                'response.hash_sha256' => $this->computeHash('sha256', $data),
                'response.hash_sha512' => $this->computeHash('sha512', $data),
                'response.compressed'  => null,
                
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
                'meta.description'      => $this->configuration->getMetadataDescription($provider, $collection, $endpoint) ?? $default,
                'meta.summary'          => $this->configuration->getMetadataSummary($provider, $collection, $endpoint) ?? $default,
                'meta.deprecated'       => $this->configuration->getMetadataDeprecated($provider, $collection, $endpoint) ?? $default,
                'meta.cache_ttl'        => $this->configuration->getMetadataCacheTTL($provider, $collection, $endpoint) ?? $default,
                'meta.tags'             => $this->configuration->getMetadataTags($provider, $collection, $endpoint) ?? $default,
                'meta.operation_id'     => $this->configuration->getMetadataOperationId($provider, $collection, $endpoint) ?? $default,
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
                    'debug.execution_time'       => $this->debug->getExecutionTime(),
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
    // Template
    // ──────────────────────────────

    public function getTemplatePath(string $provider): string 
    {
        $bundle     = $this->kernel->getBundle('ApiBundle');
        $bundlePath = $bundle->getPath();
        $path       = $this->configuration->getResponseTemplate($provider);
        $path       = Path::join($bundlePath, $path);

        if (!file_exists($path)) {
            throw new \Exception("Template not found");
        }

        return $path;
    }

    public function getTemplate(string $path) 
    {
        return match (pathinfo($path, PATHINFO_EXTENSION)) {
            'json'  => json_decode(file_get_contents($path), true) ?? [],
            'php'   => include $path,
            'xml'   => json_decode(json_encode(simplexml_load_string(file_get_contents($path))), true) ?? [],
            'yml'   => Yaml::parseFile($path) ?? [],
            'yaml'  => Yaml::parseFile($path) ?? [],
            default => throw new \Exception("Unsupported template format"),
        };
    }


    // ──────────────────────────────
    // Response times
    // ──────────────────────────────

    public function getTimestamp(): string 
    {
        return gmdate('c');
    }



    // ──────────────────────────────
    // Hash
    // ──────────────────────────────


    public function computeHash(string $algorithm, mixed $data): string 
    {
        return hash($algorithm, json_encode($data));
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