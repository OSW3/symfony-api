<?php 
namespace OSW3\Api\Service;

use Symfony\Component\Yaml\Yaml;
use OSW3\Api\HttpFoundation\XmlResponse;
use OSW3\Api\Service\ConfigurationService;
use OSW3\Api\Service\ResponseStatusService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ResponseService 
{
    private array $content = [];
    private array $data = [];
    private int $size = 0;

    public function __construct(
        private readonly ResponseStatusService $status,
        private readonly ConfigurationService $configuration,
        private readonly RouteService $routeService,
    ){}

    public function setContent(array $content): static 
    {
        $this->content = $content;

        return $this;
    }
    public function getContent(): array 
    {
        return $this->content;
    }

    public function setData(array $data): static 
    {
        // Set data 
        $this->data = $data;

        // Compute size
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->size = $jsonData !== false ? strlen($jsonData) : 0;

        return $this;
    }
    public function getData(): array 
    {
        return $this->data;
    }



    // ──────────────────────────────
    // Count & Size
    // ──────────────────────────────

    public function getCount(): int 
    {
        return is_countable($this->data) ? count($this->data) : 1;
    }
    
    public function getSize(): int 
    {
        return $this->size;
    }



    // ──────────────────────────────
    // Hash
    // ──────────────────────────────

    public function computeHash(string $algorithm): string 
    {
        $data = $this->data;
        $data = json_encode($data);
        return hash($algorithm, $data);
    }



    // ──────────────────────────────
    // Compression
    // ──────────────────────────────

    public function isCompressed(): bool 
    {
        $currentRoute = $this->routeService->getCurrentRoute();
        $context      = $currentRoute ? $currentRoute['options']['context'] : [];
        // $context    = $this->configuration->getContext();
        $provider   = $context['provider'] ?? null;

        return $this->configuration->isCompressionEnabled($provider);
    }

    public function getCompressionFormat(): string 
    {
        $currentRoute = $this->routeService->getCurrentRoute();
        $context      = $currentRoute ? $currentRoute['options']['context'] : [];
        // $context    = $this->configuration->getContext();
        $provider   = $context['provider'] ?? null;

        return $this->configuration->getCompressionFormat($provider);
    }

    public function getCompressionLevel(): int 
    {
        $currentRoute = $this->routeService->getCurrentRoute();
        $context      = $currentRoute ? $currentRoute['options']['context'] : [];
        // $context    = $this->configuration->getContext();
        $provider   = $context['provider'] ?? null;

        return $this->configuration->getCompressionLevel($provider);
    }

    public function getCompressed(): void 
    {
        $content = $this->getContent();
        $jsonContent = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($jsonContent === false) {
            return;
        }

        $compressedContent = gzencode($jsonContent, 9);
        $this->setContent(['compressed' => base64_encode($compressedContent)]);
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

    public function buildCsvResponse(array $data, int $statusCode = 200): Response 
    {
        // CSV response building logic would go here
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        // Convert $data to CSV format and set as content
        $response->setContent(''); // Placeholder
        $response->setStatusCode($statusCode);
        return $response;
    }


    public function build(): Response
    {
        $currentRoute = $this->routeService->getCurrentRoute();
        $context      = $currentRoute ? $currentRoute['options']['context'] : [];
        // $context    = $this->configuration->getContext();
        $provider   = $context['provider'] ?? null;

        $statusCode = $this->status->getCode();
        $content    = $this->getContent();


        // Build the response by format (with $content and $statusCode)
        // --

        $response = match ($this->configuration->getResponseType($provider)) {
            'csv'   => $this->buildCsvResponse($content, $statusCode),
            'json'  => $this->buildJsonResponse($content, $statusCode),
            'xml'   => $this->buildXmlResponse($content, $statusCode),
            'yaml'  => $this->buildYamlResponse($content, $statusCode),
            default => $this->buildJsonResponse($content, $statusCode),
        };


        // Add and modify headers
        // --

        // $this->headers->init($response->headers);

        // if ($this->configuration->getVersionHeaderFormat($provider) === 'header') 
        // {
        //     $this->headers->addApiVersion();
        // }


        // $this->headers->addCacheControl();


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


        if ($this->isCompressed()) {
            $this->getCompressed();
            $response->headers->set('Content-Encoding', $this->getCompressionFormat());
        }

        return $response;
    }
}