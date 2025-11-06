<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

// TODO: HEADERS SERVICE

final class HeadersService 
{
    private array $headers = [];
    private array $excludes = [];
    
    private readonly Request $request;

    public function __construct(
        private readonly AppService $appService,
        private readonly RequestStack $requestStack,
        private readonly VersionService $versionService,
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
    ){
        $this->request = $requestStack->getCurrentRequest();
    }


    // Configuration
    // ───────────────

    /**
     * Get the headers merge strategy for the current provider.
     * 
     * @return string Merge strategy ('override' or 'merge')
     */
    public function mergeStrategy(): string 
    {
        $provider = $this->configurationService->getContext('provider');
        $strategy = $this->configurationService->getHeadersMergeStrategy($provider) ?? 'append';
        return strtolower($strategy);
    }

    /**
     * Determine if the 'X-' prefix should be stripped from headers.
     * 
     * @return bool True if 'X-' prefix should be stripped, false otherwise
     */
    public function stripXPrefix(): bool 
    {
        $provider = $this->configurationService->getContext('provider');
        return $this->configurationService->isHeadersStripXPrefix($provider) ?? false;
    }

    /**
     * Determine if legacy headers should be kept.
     * 
     * @return bool True if legacy headers should be kept, false otherwise
     */
    public function keepLegacy(): bool 
    {
        $provider = $this->configurationService->getContext('provider');
        return $this->configurationService->isHeadersKeepLegacy($provider) ?? false;
    }
    
    /**
     * Get the header directives for a given type.
     * 
     * @param string $type The type of directives to retrieve ('exposed', 'vary', 'custom', 'remove')
     * @return array The header directives
     */
    public function getDirectivesList(string $type): array 
    {
        $provider = $this->configurationService->getContext('provider');

        $directives = match($type) {
            'exposed' => $this->configurationService->getHeadersExposedDirectives($provider),
            'custom'  => $this->configurationService->getHeadersCustomDirectives($provider),
            'vary'    => $this->configurationService->getHeadersVaryDirectives($provider),
            'remove'  => $this->configurationService->getHeadersRemoveDirectives($provider),
            default   => [],
        };

        return $directives;
    }


    // Builder
    // ───────────────
    
    public function addHeader(string $name, string|array $value): void 
    {
        $name  = $this->normalizeDirectiveName($name);
        $value = $this->normalizeDirectiveValue($value);

        $this->headers[$name] = $value;
    }
    

    public function buildHeaders(): array 
    {
        $headers = $this->headers;
        // dd($headers);

        // Step 1: Normalize current headers
        // $headers = $this->normalize($headers);

        // Step 2: Merge exposed headers
        $exposed = $this->getDirectivesList('exposed');
        $exposed = $this->normalize($exposed);
        $headers = $this->merge($headers, $exposed, $this->mergeStrategy());

        // Step 3: Merge custom headers
        $custom  = $this->getDirectivesList('custom');
        $custom  = $this->normalize($custom);
        $headers = $this->merge($headers, $custom, $this->mergeStrategy());


        // Step 6: Resolve directives and injection
        // --

        foreach ($headers as $key => $value) 
        {
            $xStrippedKey = strtolower(preg_replace('/^X-/i', '', $key));

            $headers[$key] = match($xStrippedKey) {
                // App
                'app-name'           => $this->appService->getName(),
                'app-vendor'         => $this->appService->getVendor(),
                'app-version'        => $this->appService->getVersion(),
                'app-description'    => $this->appService->getDescription(),
                'app-license'        => $this->appService->getLicense(),
                
                // API Version
                'api-version'            => $this->normalizeDirectiveValue($this->versionService->getLabel()),
                'api-all-versions'        => $this->normalizeDirectiveValue($this->versionService->getAllVersions()),
                'api-supported-versions'  => $this->normalizeDirectiveValue($this->versionService->getSupportedVersions()),
                'api-deprecated-versions' => $this->normalizeDirectiveValue($this->versionService->getDeprecatedVersions()),


                // 'Content_Type'  => $value === true ? $this->getContentType() : $value,
                // 'Authorization' => $value === true ? $this->request?->headers->get('Authorization') : $value,
                default         => $value,
            };
        }

        // Step 5: Strip 'X-' prefix if configured
        foreach ($headers as $key => $value) 
        {
            unset($headers[$key]);

            $originalKey = $key;
            $normalizedKey = $key;

            if (empty($value)) {
                continue;
            }

            if ($this->stripXPrefix() && str_starts_with($key, 'x-')) {
                $normalizedKey = substr($key, 2);
            }

            if ($this->keepLegacy() && $originalKey !== $normalizedKey) {
                $headers[$originalKey] = $value;
            }

            $headers[$normalizedKey] = $value;
        }

        // Step 4: Remove excluded headers
        $headers = $this->excludes($headers);

        // dd( $headers );

        return $headers;
    }



    // Utils
    // ───────────────

    /**
     * Normalize header keys by converting them to lowercase and replacing underscores/spaces with hyphens.
     * 
     * @param string|array $input The input header(s) to normalize
     * @return array The normalized headers
     */
    public function normalize(string|array $input = ''): array 
    {
        $normalized = [];

        if (is_string($input)) {
            $input = [$input];
        }

        foreach ($input as $key => $value) {
            if (is_string($key)) {
                $key   = $this->normalizeDirectiveName($key);
                $value = $this->normalizeDirectiveValue($value);

                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    private function normalizeDirectiveName(string $name): string 
    {
        $name = str_replace('_', '-', $name);
        $name = str_replace(' ', '-', $name);
        $name = strtolower($name);
        $name = implode('-', array_map('ucfirst', explode('-', $name)));

        return $name;
    }
    private function normalizeDirectiveValue(string|array $value): string 
    {
        $value = is_array($value) ? implode(', ', $value) : $value;
        $value = is_string($value) ? trim($value) : $value;

        return $value;
    }

    public function merge(array $base, array $extra, string $strategy = 'append'): array 
    {
        switch ($strategy) {
            case 'replace':
                return $extra;

            case 'ignore':
                return $base;

            case 'prepend':
                return $this->mergeUnique($extra, $base);

            case 'append':
            default:
                return $this->mergeUnique($base, $extra);
        }
    }
    private function mergeUnique(array $base, array $extra): array
    {
        foreach ($extra as $key => $value) {
            if (!array_key_exists($key, $base)) {
                $base[$key] = $value;
            }
        }
        return $base;
    }

    public function excludes(array $headers): array
    {
        $excludes = array_values(array_unique(array_filter(array_merge(
            $this->excludes,
            $this->getDirectivesList('remove')
        ))));

        foreach ($excludes as $index => $key) {
            unset($excludes[$index]);
            $key = str_replace('_', '-', $key);
            $key = str_replace(' ', '-', $key);
            $key = strtolower($key);
            $xStrippedKey = preg_replace('/^x-/i', '', $key);

            $excludes[] = $key;
            $excludes[] = $xStrippedKey;
        }

        return array_filter($headers, function($key) use ($excludes) {
            $key = str_replace('_', '-', $key);
            $key = str_replace(' ', '-', $key);
            $key = strtolower($key);
            $xStrippedKey = preg_replace('/^x-/i', '', $key);
            
            return !in_array($xStrippedKey, $excludes, true);
        }, ARRAY_FILTER_USE_KEY);
    }


    // Resolvers
    // ───────────────

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




    // public function resolveHeadersValue(): array 
    // {
    //     $headers = [];

    //     // Exposed Directives
    //     $directives = $this->getDirectives('exposed');

    //     foreach ($directives as $name => $value) 
    //     {
    //         if (!$value) continue;

    //         // $headers[$name] = $value;
    //         $headers[$name] = match($name) {
    //             'Content_Type'  => $value === true ? $this->getContentType() : $value,
    //             'Authorization' => $this->request?->headers->get('Authorization'),
    //             default         => $value,
    //         };
    //     }




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

    //     foreach ($headers as $key => $value) {
            
    //         if (in_array($key, $this->excludes, true)) {
    //             unset($headers[$key]);
    //         }

    //         if ($value === false || $value === true || $value === null) {
    //             unset($headers[$key]);
    //         }
    //     }

    //     return $headers;
    // }



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
//    if ($this->configuration->isDeprecated($provider)) {
        // X-API-Deprecated: true
//    }



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