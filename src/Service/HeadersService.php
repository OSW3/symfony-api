<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

// TODO: HEADERS SERVICE

final class HeadersService 
{
    private ResponseHeaderBag $headers;
    private array $excludes = ['X-Powered-By'];
    
    private readonly Request $request;

    public function __construct(
        private readonly VersionService $versionService,
        private readonly AppService $appService,
        private readonly ConfigurationService $configurationService,
        private readonly RequestStack $requestStack,
    ){
        $this->request = $requestStack->getCurrentRequest();
    }

    public function mergeStrategy(): string 
    {
        $provider = $this->configurationService->getContext('provider');
        return $this->configurationService->getHeadersMergeStrategy($provider) ?? 'override';
    }

    public function stripXPrefix(): bool 
    {
        $provider = $this->configurationService->getContext('provider');
        return $this->configurationService->isHeadersStripXPrefix($provider) ?? false;
    }

    public function keepLegacy(): bool 
    {
        $provider = $this->configurationService->getContext('provider');
        return $this->configurationService->isHeadersKeepLegacy($provider) ?? false;
    }
    
    public function getDirectives(string $type): array 
    {
        $provider = $this->configurationService->getContext('provider');

        return match($type) {
            'exposed' => $this->configurationService->getHeadersExposedDirectives($provider),
            'vary'    => $this->configurationService->getHeadersVaryDirectives($provider),
            'custom'  => $this->configurationService->getHeadersCustomDirectives($provider),
            'remove'  => $this->configurationService->getHeadersRemoveDirectives($provider),
            default   => [],
        };
    }

    
    

    /**
     * Get the response format for the current request, considering possible overrides.
     * 
     * @return string Response format (e.g., 'json', 'xml', etc.)
     */
    public function getFormat(): string 
    {
        $provider = $this->configurationService->getContext('provider');
        $format   = $this->configurationService->getResponseType($provider);

        $canOverride = $this->configurationService->canOverrideResponseType($provider);
        $parameter   = $this->configurationService->getResponseFormatParameter($provider);

        if ($canOverride) {
            $requestedFormat = $this->request->get($parameter);
            if ($requestedFormat) {
                $format = $requestedFormat;
            }
        }

        return $format;
    }

    /**
     * Get the Content-Type header value based on the response format.
     * 
     * @return string Content-Type header value
     */
    public function getContentType(): string 
    {
        $format   = $this->getFormat();

        return match($format) {
            'json' => 'application/json',
            'xml'  => 'application/xml',
            'yaml' => 'application/x-yaml',
            'csv'  => 'text/csv',
            default => 'application/json',
        };
    }




    public function resolveHeadersValue(): array 
    {
        $headers = [];

        // Exposed Directives
        $directives = $this->getDirectives('exposed');

        foreach ($directives as $name => $value) 
        {
            if (!$value) continue;

            // $headers[$name] = $value;
            $headers[$name] = match($name) {
                'Content_Type'  => $value === true ? $this->getContentType() : $value,
                'Authorization' => $this->request?->headers->get('Authorization'),
                default         => $value,
            };
        }




        // if (!empty($exposedDirectives)) {
        //     $headers['Access-Control-Expose-Headers'] = implode(', ', $exposedDirectives);
        // }

        // // Vary Directives
        // $varyDirectives = $this->getDirectives('vary');
        // if (!empty($varyDirectives)) {
        //     $headers['Vary'] = implode(', ', $varyDirectives);
        // }

        // // Custom Directives
        // $customDirectives = $this->getDirectives('custom');
        // foreach ($customDirectives as $key => $value) {
        //     $headers[$key] = $value;
        // }

        foreach ($headers as $key => $value) {
            
            if (in_array($key, $this->excludes, true)) {
                unset($headers[$key]);
            }

            if ($value === false || $value === true || $value === null) {
                unset($headers[$key]);
            }
        }

        return $headers;
    }



    // Content-Type: true
    // Content-Language: true
    // Content-Disposition: true
    // Authorization: true
    // API-Version: true
    // X-Auth-Token: true
    // X-Api-Key: true
    // Request-ID: true
    // Correlation-ID: true
    // Trace-ID: true
    // Response-Time: true


    // If rate limit enabled + rate limit in headers
    // RateLimit-Limit: true
    // RateLimit-Remaining: true
    // RateLimit-Reset: true   



    // TODO: Entete si la version de l'api est dépreciée



    // public function init(ResponseHeaderBag $headers): static 
    // {
    //     $this->headers = $headers;

    //     // dd($this->headers);

    //     return $this;
    // }

    // public function all(): ResponseHeaderBag 
    // {
    //     foreach ($this->excludes as $property) 
    //     {
    //         // unset($this->headers[$property]);
    //     }


    //     // dd($this->headers);
    //     return $this->headers;
    // }

    // public function addApiVersion(): static 
    // {
    //     $provider = $this->configuration->getContext('provider');
    //     $vendor   = $this->app->getVendor();
    //     $version  = $this->version->getLabel();
    //     $pattern  = $this->configuration->getVersionHeaderFormat($provider);
    //     $pattern  = preg_replace("/{vendor}/", $vendor, $pattern);
    //     $pattern  = preg_replace("/{version}/", $version, $pattern);
        
    //     $this->headers->set('API-Version', $version);
    //     $this->headers->set('Accept', $pattern);

    //     return $this;
    // }

    // public function addCacheControl(): static 
    // {
    //     $provider = $this->configuration->getContext('provider');
    //     $this->headers->set('Cache-Control', $this->configuration->getResponseCacheControl($provider) ?? null);

    //     return $this;
    // }

}