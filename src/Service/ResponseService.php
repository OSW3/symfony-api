<?php 
namespace OSW3\Api\Service;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Path;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
// use Symfony\Component\Serializer\SerializerInterface;

final class ResponseService 
{
    private int $statusCode = Response::HTTP_OK;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly ConfigurationService $configuration,
        // private readonly SerializerInterface $serializer,
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
    // API
    // ──────────────────────────────


    // ──────────────────────────────
    // Response
    // ──────────────────────────────
    public function setResponseStatusCode(int $statusCode): static 
    {
        $this->statusCode = $statusCode;
        return $this;
    }
    public function getResponseStatusCode(): int 
    {
        return $this->statusCode;
    }

    public function getResponseStatusText(): string 
    {
        return Response::$statusTexts[ $this->getResponseStatusCode() ];
    }

    public function getResponseTimestamp(): string 
    {
        return gmdate('c');
    }


    // ──────────────────────────────
    // MetaData
    // ──────────────────────────────


    // ──────────────────────────────
    // Error
    // ──────────────────────────────

    public function getStatus(): string 
    {
        return "success";
    }


    public function buildResponse(mixed $data)
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



        // switch (gettype($data)) {
        //     case 'array': foreach ($data as $key => $item) $data[$key] = $this->serialize($item); break;
        //     case 'object': $data = $this->serialize($data); break;
        //     default: $data = [];
        // }


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
                'api.version'      => $this->configuration->getVersion($provider) ?? $default,

                'response.status'      => $this->getStatus() ?? $default,
                'response.statusCode'  => $this->getResponseStatusCode() ?? $default,
                'response.statusText'  => $this->getResponseStatusText() ?? $default,
                'response.timestamp'  => $this->getResponseTimestamp(),
                'response.data'        => $data,

                'metadata.description' => $this->configuration->getMetadataDescription($provider, $collection, $endpoint) ?? $default,
                'metadata.summary'     => $this->configuration->getMetadataSummary($provider, $collection, $endpoint) ?? $default,
                'metadata.deprecated'  => $this->configuration->getMetadataDeprecated($provider, $collection, $endpoint) ?? $default,
                'metadata.cacheTTL'    => $this->configuration->getMetadataCacheTTL($provider, $collection, $endpoint) ?? $default,
                'metadata.tags'        => $this->configuration->getMetadataTags($provider, $collection, $endpoint) ?? $default,
                'metadata.operationId' => $this->configuration->getMetadataOperationId($provider, $collection, $endpoint) ?? $default,

                default                => $default ?? $value,
            };

        });

        return $template;
    }





    // private function template()
    // {
    //     return $rootNode()
    //         ->statusNode('status')->end()
    //         ->timestampNode('timestamp')->end()
    //         ->errorNode('error')->end()
    //         ->dataNode('data')->end()
    //         ->metaNode('meta')
    //             ->timestampNode('timestamp')->end()
    //             ->requestIdNode('requestId')->end()
    //             ->versionNode('version')->end()
    //             ->integerNode('totalItems')->value(3)->end()
    //             ->integerNode('totalPages')->value(2)->end()
    //             ->integerNode('currentPage')->value(1)->end()
    //             ->integerNode('perPage')->value(1)->end()
    //             ->urlNode('documentationUrl')->value("https://api.example.com/docs/users")->end()
    //         ->end()
    //     ->end();
    // }









}