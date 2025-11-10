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
    private array $hashCache = [];

    public function __construct(
        private readonly RouteService $routeService,
        private readonly ResponseStatusService $status,
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configuration,
        private readonly RequestService $requestService,
        private readonly ToonEncoder $toonEncoder,
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

    public function getFormat(): string
    {
        $provider = $this->contextService->getProvider();
        $default = $this->configuration->getResponseType($provider);
        $format = $default;

        $canOverrideResponseType = $this->configuration->canOverrideResponseType($provider);

        
        if ($canOverrideResponseType) {
            $request = $this->requestService->getCurrentRequest();
            $param = $this->configuration->getResponseFormatParameter($provider);
            $requestedFormat = $request->query->get($param);
            
            $validFormats = array_keys(MimeType::toArray(true));

            if (in_array($requestedFormat, $validFormats, true)) {
                $format = $requestedFormat;
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
        if (!isset($this->hashCache[$algorithm])) {
            $data = $this->data;
            $data = json_encode($data);
            $this->hashCache[$algorithm] = hash($algorithm, $data);
        }

        return $this->hashCache[$algorithm];
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