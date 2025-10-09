<?php 
namespace OSW3\Api\Service;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Path;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

final class ResponseService 
{
    private Request $request;
    private int $statusCode = Response::HTTP_OK;
    private array $headers = [];
    private string $tseStart;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly ConfigurationService $configuration,
        private readonly RequestStack $requestStack,
    ){
        $this->request = $requestStack->getCurrentRequest();
    }

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
        ['provider' => $provider,'collection' => $collection,'endpoint' => $endpoint] = $this->getContext();


        $this->setStatusCode(418);

        $statusCode = $this->getStatusCode();
        $payload    = $this->payload($data);

        $response = new JsonResponse($payload);
        $response->setStatusCode($statusCode);


        unset($this->headers['X-Powered-By']);


        $version = $this->configuration->getVersion($provider);
        $versionType = $this->configuration->getVersionType($provider);
        $versionFormat = $this->configuration->getVersionHeaderFormat($provider);

        if ( $versionType === 'header') {
            $this->headers['Accept'] = $versionFormat;
            $this->headers['API-Version'] = $version;
        }



        foreach ($this->headers as $key => $value) 
        {
            $response->headers->set($key, $value);
        }


        // $response->headers->remove('X-Powered-By');

        // $response->headers->set('X-App-Version', '1.2.3');
        // $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        // $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }


    public function payload(mixed $data)
    {
        ['provider' => $provider,'collection' => $collection,'endpoint' => $endpoint] = $this->getContext();

        $provider     = $this->configuration->guessProvider();
        $bundle       = $this->kernel->getBundle('ApiBundle');
        $bundlePath   = $bundle->getPath();
        $templatePath = $this->configuration->getTemplate($provider);
        $templatePath = Path::join($bundlePath, $templatePath);

        if (!file_exists($templatePath)) {
            throw new \Exception("Template not found");
        }

        $template = Yaml::parseFile($templatePath);




        // Remplacement des expressions {xxx,yyy} dans le template
        array_walk_recursive($template, function (&$value, $k) use ($data, $provider, $collection, $endpoint) {
            
            if (!is_string($value)) {
                return;
            }

            $expression = null;
            $default    = null;

            if (preg_match('/\{([^,}]+)(?:,\s*([^\}]+))?\}/', $value, $matches)) {
                $expression = trim($matches[1]);
                $default    = isset($matches[2]) ? trim($matches[2]) : null;
            }

            // Calcul de la valeur finale
            $value = match($expression) {
                // Current API Version (v1, v2, ...)
                'api.version'           => $this->configuration->getVersion($provider) ?? $default,
                'api.deprecated'        => $this->configuration->isDeprecated($provider),
                'api.name'              => null,
                'api.vendor'            => null,
                'api.base_url'          => null,
                'api.doc_url'           => null,

                // Request
                'request.method'        => $this->request->getMethod(),
                'request.uri'           => $this->request->getUri(),
                'request.path'          => $this->request->getPathInfo(),
                'request.query'         => $this->request->query->all(),
                'request.ip'            => $this->request->getClientIp(),
                'request.user_agent'    => $this->request->headers->get('User-Agent'),
                'request.locale'        => $this->request->getLocale(),
                'request.id'            => null,
                'request.referer'       => null,
                'request.scheme'        => null,
                'request.body_size'     => null,
                'request.content_type'  => null,

                // Response Status
                'status.state'          => $this->getStatusState() ?? $default,
                'status.reason'         => $this->getStatusReason() ?? $default,
                'status.code'           => $this->getStatusCode() ?? $default,

                // Server
                'server.host'           => gethostname(),
                'server.env'            => $_ENV['APP_ENV'] ?? 'prod',
                'server.php_version'    => PHP_VERSION,
                'server.framework'      => 'Symfony ' . \Symfony\Component\HttpKernel\Kernel::VERSION,
                'server.name'           => null,
                'server.os'             => null,
                'server.uptime'         => null,
                'server.app_version'    => null,
                'server.timezone'       => null,

                // Pagination
                'pagination.page'       => 0, // $this->pagination->getPage() ?? 1,
                'pagination.limit'      => 0, // $this->pagination->getLimit() ?? 25,
                'pagination.total'      => 0, // $this->pagination->getTotal() ?? 0,
                'pagination.pages'      => 0, // $this->pagination->getTotalPages() ?? 0,

                // User
                'user.id'               => null, //$this->security->getUser()?->getId(),
                'user.username'         => null, //$this->security->getUser()?->getUserIdentifier(),
                'user.roles'            => null, //$this->security->getUser()?->getRoles(),
                'user.email'            => null,
                'user.authenticated'    => null,
                'user.token_issued_at'  => null,
                'user.token_expires_at' => null,
                'user.permissions'      => null,

                // Debug
                'debug.memory'          => memory_get_usage(true),
                'debug.peak_memory'     => memory_get_peak_usage(true),
                'debug.execution_time'  => $this->getTse(),
                'debug.db_queries'      => null,
                'debug.db_time'         => null,
                'debug.cache_hits'      => null,
                'debug.cache_misses'    => null,
                'debug.log_level'       => null,
                'debug.included_files'  => null,

                // Resource
                'resource.provider'     => $provider,
                'resource.collection'   => $collection,
                'resource.endpoint'     => $endpoint,
                'resource.count'        => is_countable($data) ? count($data) : 1,

                // Response
                'response.timestamp'    => $this->getTimestamp(),
                'response.error'        => "error",
                'response.data'         => $data,
                'response.size'         => null,
                'response.hash'         => md5(json_encode($data)),
                'response.compressed'   => null,
                'response.cache_control'=> null,
                'response.tse'          => null,

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

                default                => $default ?? $value,
            };

        });

        return $template;
    }



    // ──────────────────────────────
    // API
    // ──────────────────────────────


    // ──────────────────────────────
    // Status
    // ──────────────────────────────

    public function setStatusCode(int $statusCode): static 
    {
        $this->statusCode = $statusCode;
        return $this;
    }
    public function getStatusCode(): int 
    {
        return $this->statusCode;
    }

    public function getStatusState(): string 
    {
        $code = $this->getStatusCode();

        return match (true) {
            $code >= 200 && $code < 300 => 'success',
            $code >= 400 && $code < 500 => 'failed',
            default                     => 'error',
        };
    }

    public function getStatusReason(): string 
    {
        return Response::$statusTexts[ $this->getStatusCode() ];
    }


    // ──────────────────────────────
    // Response times
    // ──────────────────────────────

    public function setTseStart(): static 
    {
        $this->tseStart = microtime(true);

        // dd($this->tseStart);
        return $this;
    }
    public function getTseStart(): string 
    {
        return $this->tseStart;
    }

    public function getTse(): string 
    {
        return (microtime(true) - $this->getTseStart()) * 1000;
    }

    public function getTimestamp(): string 
    {
        return gmdate('c');
    }


    // ──────────────────────────────
    // MetaData
    // ──────────────────────────────


    // ──────────────────────────────
    // Error
    // ──────────────────────────────
}