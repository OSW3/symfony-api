<?php 
namespace OSW3\Api\Service;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Path;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ResponseService 
{
    private int $statusCode = Response::HTTP_OK;
    private array $headers = [];
    private string $tseStart;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly ConfigurationService $configuration,
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
        ['provider' => $provider,'collection' => $collection,'endpoint' => $endpoint] = $this->getContext();


        $this->setStatusCode(418);

        $statusCode = $this->getStatusCode();
        $payload    = $this->payload($data);

        $response = new JsonResponse($payload);
        $response->setStatusCode($statusCode);


        unset($this->headers['X-Powered-By']);


        $version = $this->configuration->getVersion($provider);
        $versionType = $this->configuration->getVersionType($provider);

        if ( $versionType === 'header') {
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

                // Response Status
                'status.state'          => $this->getStatusState() ?? $default,
                'status.reason'         => $this->getStatusReason() ?? $default,
                'status.code'           => $this->getStatusCode() ?? $default,

                'response.timestamp'    => $this->getTimestamp(),
                'response.tse'          => $this->getTse(),
                'response.data'         => $data,

                'metadata.description'  => $this->configuration->getMetadataDescription($provider, $collection, $endpoint) ?? $default,
                'metadata.summary'      => $this->configuration->getMetadataSummary($provider, $collection, $endpoint) ?? $default,
                'metadata.deprecated'   => $this->configuration->getMetadataDeprecated($provider, $collection, $endpoint) ?? $default,
                'metadata.cacheTTL'     => $this->configuration->getMetadataCacheTTL($provider, $collection, $endpoint) ?? $default,
                'metadata.tags'         => $this->configuration->getMetadataTags($provider, $collection, $endpoint) ?? $default,
                'metadata.operationId'  => $this->configuration->getMetadataOperationId($provider, $collection, $endpoint) ?? $default,

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