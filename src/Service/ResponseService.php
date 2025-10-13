<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\AppService;
use Symfony\Component\Yaml\Yaml;
use OSW3\Api\Service\ClientService;
use OSW3\Api\Service\RequestService;
use Symfony\Component\Filesystem\Path;
use OSW3\Api\Service\ConfigurationService;
use OSW3\Api\Service\DocumentationService;
use OSW3\Api\Service\ResponseStatusService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ResponseService 
{
    public function __construct(
        private readonly AppService $app,
        private readonly DebugService $debug,
        private readonly ClientService $client,
        private readonly ServerService $server,
        private readonly HeaderService $headers,
        private readonly KernelInterface $kernel,
        private readonly RequestService $request,
        private readonly SecurityService $security,
        private readonly PaginationService $pagination,
        private readonly ResponseStatusService $status,
        private readonly ConfigurationService $configuration,
        private readonly DocumentationService $documentation,
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

    public function create(array $data): Response
    {
        // ['provider' => $provider,'collection' => $collection,'endpoint' => $endpoint] = $this->getContext();

        $statusCode = $this->status->getCode();
        $payload    = $this->payload($data);
        $response   = new JsonResponse();
        

        $this->headers
            ->init($response->headers)
            ->addApiVersion()
        ;


        // dump($response->headers->all());

        // foreach ($this->headers->all() as $key => $value) 
        // {
        //     $response->headers->set($key, $value);
        // }
        // dd($response->headers->all());


        // $response->headers->set('X-App-Version', '1.2.3');
        // $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        // $response->headers->set('Access-Control-Allow-Origin', '*');

        $response->setContent(json_encode($payload));
        $response->setStatusCode($statusCode);

        return $response;
    }

    public function payload(mixed $data)
    {
        ['provider' => $provider,'collection' => $collection,'endpoint' => $endpoint] = $this->getContext();

        $bundle       = $this->kernel->getBundle('ApiBundle');
        $bundlePath   = $bundle->getPath();
        $templatePath = $this->configuration->getTemplate($provider);
        $templatePath = Path::join($bundlePath, $templatePath);

        if (!file_exists($templatePath)) {
            throw new \Exception("Template not found");
        }

        $template = Yaml::parseFile($templatePath);

        array_walk_recursive($template, function (&$value, $k) use ($data, $provider, $collection, $endpoint) {
            
            if (!is_string($value)) return;

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
                'api.version'            => $this->configuration->getVersion($provider),
                'api.version.number'     => $this->configuration->getVersionNumber($provider),
                'api.version.prefix'     => $this->configuration->getVersionPrefix($provider),
                'api.supported_versions' => $this->configuration->getAllVersions(),
                'api.deprecated'         => $this->configuration->isDeprecated($provider),
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



            $value = match($expression) {

                // User
                'user.id'                    => $this->security->getId(),
                'user.username'              => $this->security->getUserName(),
                'user.roles'                 => $this->security->getRoles(),
                'user.email'                 => $this->security->getEmail(),
                'user.token_issued_at'       => null,
                'user.token_expires_at'      => null,
                'user.token_scopes'          => [],
                'user.permissions'           => null,
                'user.mfa_enabled'           => null,

                // Debug
                // 'debug.memory'               => $this->debug->getMemoryUsage(),
                // 'debug.peak_memory'          => $this->debug->getMemoryPeak(),
                // 'debug.execution_time'       => $this->debug->getExecutionTime(),
                // 'debug.log_level'            => $this->debug->getLogLevel(),
                // 'debug.count_included_files' => $this->debug->getCountIncludedFiles(),
                // 'debug.included_files'       => $this->debug->getIncludedFiles(),
                // // 'debug.queue_time'           => $this->debug->getQueueTime(),

                // Resource
                'resource.provider'          => $provider,
                'resource.collection'        => $collection,
                'resource.endpoint'          => $endpoint,
                'resource.count'             => is_countable($data) ? count($data) : 1,

                // Response
                'response.timestamp'     => gmdate('c'),
                'response.data'          => $data,
                'response.size'          => null,
                'response.hash'          => md5(json_encode($data)),
                'response.compressed'    => null,
                'response.cache_control' => null,
                'response.tse'           => null,
                'response.error.code'    => null,
                'response.error.message' => null,
                'response.error.details' => null,
                'response.error.doc_url' => null,
                'response.etag'          => null,
                'response.format'        => null,
                'response.latency'       => null,
                'response.validated'     => null,
                'response.signature'     => null,
                'response.cache_key'     => null,
                'response.cors'          => null,
                'response.locale'        => null,

                // Metadata
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

                // Rate Limit
                'rate_limit.limit'     => null,
                'rate_limit.remaining' => null,
                'rate_limit.reset'     => null,

                default                => $default ?? $value,
            };


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
    
                    default                => $default ?? $value,
                };
            }

        });

        return $template;
    }



    // ──────────────────────────────
    // Response times
    // ──────────────────────────────

    public function getTimestamp(): string 
    {
        return gmdate('c');
    }
}