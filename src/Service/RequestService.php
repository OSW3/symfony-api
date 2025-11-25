<?php 
namespace OSW3\Api\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestService 
{
    private readonly Request $request;

    public function __construct(
        private readonly ContextService $contextService,
        private readonly RouteService $routeService,
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
    public function getCurrentRequest(): Request 
    {
        return $this->request;
    }


    // ──────────────────────────────
    // Current Route 
    // ──────────────────────────────

    public function getCurrentRoute(): ?string 
    {
        return $this->request->attributes->get('_route') ?? null;
    }

    

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
        return $this->request->isSecure();
    }

    public function isFromTrustedProxy(): bool
    {
        return $this->request->isFromTrustedProxy();
    }

    /**
     * Get the base URL (scheme + host + port + basePath)
     * 
     * @deprecated use getBaseUrl() instead
     * @return string
     */
    public function getBase(): string 
    {
        return $this->getBaseUrl();
    }
    public function getBaseUrl(): string 
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
     * @deprecated use getPath() or getPathInfo() instead
     * @var string
     */
    public function getPath(): string 
    {
        return $this->request->getPathInfo();
    }
    public function getPathInfo(): string 
    {
        return $this->request->getPathInfo();
    }

    public function isAjax(): bool
    {
        return $this->request->isXmlHttpRequest();
    }


    // ──────────────────────────────
    // Params
    // ──────────────────────────────

    /**
     * Get all params
     * 
     * @return array
     */
    public function getParameters(): array
    {
        return array_merge(
            $this->getQueryParameters(),
            $this->getRequestParameters(),
            $this->getAttributesParameters()
        );
    }

    /**
     * Get the query params
     * 
     * @deprecated use getQueryParameters() instead
     * @return array
     */
    public function getQueryParams(): array 
    {
        return $this->request->query->all();
    }
    public function getQueryParameters(): array 
    {
        return $this->request->query->all();
    }

    /**
     * Get the request params
     * 
     * @deprecated use getRequestParameters() instead
     * @return array
     */
    public function getRequestParams(): array 
    {
        return $this->request->request->all();
    }
    public function getRequestParameters(): array 
    {
        return $this->request->request->all();
    }

    /**
     * Get the attributes params
     * 
     * @deprecated use getAttributesParameters() instead
     * @return array
     */
    public function getAttributesParams(): array 
    {
        return $this->request->attributes->all();
    }
    public function getAttributesParameters(): array 
    {
        return $this->request->attributes->all();
    }

    /**
     * Check if all required params are present in the request
     * 
     * @return bool
     */
    public function hasRequiredParameters(): bool 
    {
        $required = $this->routeService->getRequirements(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );
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

    // public function getHeaders(): array
    // {
    //     return $this->request->headers->all();
    // }

    // public function getRawContent(): string
    // {
    //     return $this->request->getContent();
    // }
    // public function getFormat(): string
    // {
    //     return $this->request->getRequestFormat();
    // }


    // public function getSorter(): array 
    // {
    //     return [];
    // }
}