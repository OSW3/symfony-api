<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Enum\MimeType;
use Symfony\Component\Yaml\Yaml;
use OSW3\Api\Encoder\ToonEncoder;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\ConfigurationService;
use OSW3\Api\Service\ResponseStatusService;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;

final class ResponseService 
{
    private array $content = [];
    private array $data = [];
    private int $size = 0;
    private int $count = 0;
    private array $hashCache = [];

    public function __construct(
        private readonly RouteService $routeService,
        private readonly ResponseStatusService $status,
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configuration,
        private readonly RequestService $requestService,
        private readonly ToonEncoder $toonEncoder,
    ){}

    // Configuration

    /**
     * Get the response format
     * Gets the default format from configuration
     * Allows override via query parameter if enabled
     * 
     * @return string
     */
    public function getFormat(): string
    {
        $provider = $this->contextService->getProvider();

        // Get the format from configuration
        $format = $this->configuration->getResponseType($provider);

        // Check if format override is allowed
        if ($this->configuration->canOverrideResponseType($provider)) 
        {
            // Get the current request
            $request = $this->requestService->getCurrentRequest();

            // Get the parameter name for format override
            $param = $this->configuration->getResponseFormatParameter($provider);

            // Retrieve the custom format from query parameters
            $custom = $request->query->get($param);
            
            // Validate and set the custom format if it's valid
            if (in_array($custom, array_keys(MimeType::toArray(true)), true)) {
                $format = $custom;
            }
        }

        return $format;
    }

    public function getMimeType(): string
    {
        $provider = $this->contextService->getProvider();

        $configuredMimeType = $this->configuration->getResponseMimeType($provider);
        if ($configuredMimeType !== null) {
            return $configuredMimeType;
        }

        $format = $this->getFormat();
        return MimeType::fromFormat($format)->value;
    }


    // Getter / Setter

    public function setSize(int $size): static 
    {
        $this->size = $size;

        return $this;
    }
    public function getSize(): int 
    {
        return $this->size;
    }

    public function setCount(int $count): static 
    {
        $this->count = $count;

        return $this;
    }
    public function getCount(): int 
    {
        return $this->count;
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
    // JSON, XML, YAML, CSV, TOON, TOML
    // ──────────────────────────────


    public function getXmlResponse(string $data): ?string
    {
        $data       = json_decode($data, true);
        $serializer = new Serializer([], [new XmlEncoder()]);

        return $serializer->encode($data, 'xml');
    }

    public function getYamlResponse(string $data): ?string 
    {
        $data = json_decode($data, true);
        return Yaml::dump($data, 2, 4, Yaml::DUMP_OBJECT_AS_MAP);
    }

    public function getCsvResponse(string $data): ?string
    {
        $data       = json_decode($data, true);
        $serializer = new Serializer([], [new CsvEncoder()]);
        
        return $serializer->encode($data, 'csv');
    }

    public function getToonResponse(string $data): ?string
    {
        $data       = json_decode($data, true);
        return $this->toonEncoder->encode($data, 'toon');
    }
}