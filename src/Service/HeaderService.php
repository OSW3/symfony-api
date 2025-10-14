<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

final class HeaderService 
{
    private ResponseHeaderBag $headers;
    private array $excludes = ['X-Powered-By'];

    public function __construct(
        private readonly ApiService $api,
        private readonly AppService $app,
        private readonly ConfigurationService $configuration,
    ){}


    public function init(ResponseHeaderBag $headers): static 
    {
        $this->headers = $headers;

        // dd($this->headers);

        return $this;
    }

    public function all(): ResponseHeaderBag 
    {
        foreach ($this->excludes as $property) 
        {
            // unset($this->headers[$property]);
        }


        // dd($this->headers);
        return $this->headers;
    }

    public function addApiVersion(): static 
    {
        $provider = $this->configuration->guessProvider();
        $vendor   = $this->app->getVendor();
        $version  = $this->api->getFullVersion();
        $pattern  = $this->configuration->getVersionHeaderFormat($provider);
        $pattern  = preg_replace("/{vendor}/", $vendor, $pattern);
        $pattern  = preg_replace("/{version}/", $version, $pattern);
        
        $this->headers->set('API-Version', $version);
        $this->headers->set('Accept', $pattern);

        return $this;
    }

    public function addCacheControl(): static 
    {
        $provider = $this->configuration->guessProvider();
        $this->headers->set('Cache-Control', $this->configuration->getResponseCacheControl($provider) ?? null);

        return $this;
    }

}