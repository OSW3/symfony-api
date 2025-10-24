<?php 
namespace OSW3\Api\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestService 
{
    private readonly Request $request;

    public function __construct(
        private readonly ConfigurationService $configuration,
        private readonly RequestStack $requestStack,
    ){
        $this->request = $requestStack->getCurrentRequest();
    }


    // ──────────────────────────────
    // Current Request 
    // ──────────────────────────────

    /**
     * Get current request
     * 
     * @return Request
     */
    public function getRequest(): Request 
    {
        return $this->getCurrentRequest();
    }
    public function getCurrentRequest(): Request 
    {
        return $this->request;
    }


    public function getCurrentRoute(): string 
    {
        return $this->request->attributes->get('_route');
    }


    // ──────────────────────────────
    // HTTP Request
    // ──────────────────────────────

    /**
     * Get the HTTP Method (GET, POST, ...)
     * 
     * @return string
     */
    public function getMethod(): string 
    {
        return $this->request->getMethod();
    }


    // ──────────────────────────────
    // URI Fragments
    // ──────────────────────────────

    /**
     * Get the request scheme (HTTP, HTTPS)
     * 
     * @return string
     */
    public function getScheme(): string 
    {
        if (!$this->request) {
            return 'http';
        }

        return $this->request->getScheme();
    }

    /**
     * Return true if HTTPS
     * 
     * @return bool
     */
    public function isSecure(): bool
    {
        return str_ends_with("s", $this->getScheme());
    }

    /**
     * Get the base URL (scheme + host + port + basePath)
     * 
     * @return string
     */
    public function getBase(): string 
    {
        $scheme   = $this->request->getScheme();
        $host     = $this->request->getHost();
        $port     = $this->getPort();
        $basePath = rtrim($this->request->getBasePath(), '/');
        $portPart = in_array($port, [80, 443], true) ? '' : ":{$port}";

        return sprintf('%s://%s%s%s', $scheme, $host, $portPart, $basePath);
    }

    /**
     * Get the request port
     * 
     * @return int
     */
    public function getPort(): int 
    {
        return $this->request->getPort();
    }

    /**
     * Get the request URI
     * 
     * @return string
     */
    public function getUri(): string 
    {
        return $this->request->getUri();
    }

    /**
     * Get the URI Path
     * 
     * @var string
     */
    public function getPath(): string 
    {
        return $this->request->getPathInfo();
    }


    // ──────────────────────────────
    // Params
    // ──────────────────────────────

    /**
     * Get all params
     * 
     * @return array
     */
    public function getParams(): array
    {
        return array_merge(
            $this->getQueryParams(),
            $this->getRequestParams(),
            $this->getAttributesParams()
        );
    }

    /**
     * Get the query params
     * 
     * @return array
     */
    public function getQueryParams(): array 
    {
        return $this->request->query->all();
    }

    /**
     * Get the request params
     * 
     * @return array
     */
    public function getRequestParams(): array 
    {
        return $this->request->request->all();
    }

    /**
     * Get the attributes params
     * 
     * @return array
     */
    public function getAttributesParams(): array 
    {
        return $this->request->attributes->all();
    }

    /**
     * Check if all required params are present in the request
     * 
     * @return bool
     */
    public function hasRequiredParams(): bool 
    {
        ['provider' => $provider, 'collection' => $collection, 'endpoint' => $endpoint] = $this->configuration->getContext();
        $required    = $this->configuration->getRouteRequirements($provider, $collection, $endpoint);
        $routeParams = $this->request->attributes->get('_route_params', []);

        foreach (array_keys($required) as $param) {
            if (!array_key_exists($param, $routeParams)) {
                return false;
            }
        }

        return true;
    }

    
    // ──────────────────────────────
    // Xxxxx
    // ──────────────────────────────

    /**
     * Request locale
     * 
     * @return string
     */
    public function getLocale(): string 
    {
        return $this->request->getLocale();
    }

    
    // ──────────────────────────────
    // Xxxxx
    // ──────────────────────────────

    public function getHeaders(): array
    {
        return $this->request->headers->all();
    }

    public function getRawContent(): string
    {
        return $this->request->getContent();
    }
    public function getFormat(): string
    {
        return $this->request->getRequestFormat();
    }


    public function getEntityClassname(): string|null
    {
        // $providers = $this->configuration->getProviders();
        // $current = $this->requestStack->getCurrentRequest()->get('_route');

        // foreach ($providers as $provider) 
        // foreach ($provider['collections'] ?? [] as $entityName => $collections) 
        // foreach ($collections['endpoints'] ?? [] as $endpointName => $endpointOption) 
        // {
        //     $routeName = $endpointOption['name'];

        //     if ($current == $routeName) {
        //         return $entityName;
        //     }
        // }
        
        return null;
    }

    /**
     * Get the repository method
     *
     * @return string|null
     */
    public function getRepositoryMethod(): string|null 
    {
        // $providers = $this->configuration->getProviders();
        // $current = $this->requestStack->getCurrentRequest()->get('_route');

        // foreach ($providers as $provider) 
        // foreach ($provider['collections'] ?? [] as $entityName => $collections) 
        // foreach ($collections['endpoints'] ?? [] as $endpointName => $endpointOption) 
        // {
        //     $routeName = $endpointOption['name'];

        //     if ($current == $routeName) {
        //         return $endpointOption['repository']['method'];
        //     }
        // }

        return null;
    }
       
    public function getRepositoryCriteria(): array
    {
        // $providers = $this->configuration->getProviders();
        // $current = $this->requestStack->getCurrentRequest()->get('_route');

        // foreach ($providers as $provider) 
        // foreach ($provider['collections'] ?? [] as $entityName => $collections) 
        // foreach ($collections['endpoints'] ?? [] as $endpointName => $endpointOption) 
        // {
        //     $routeName = $endpointOption['name'];

        //     if ($current == $routeName) {
        //         return $endpointOption['repository']['criteria'];
        //     }
        // }

        return [];
    }

    public function getSorter(): array 
    {
        return [];
    }
}