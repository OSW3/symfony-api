<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

final class HeaderService 
{
    private ResponseHeaderBag $headers;
    private array $excludes = ['X-Powered-By'];

    public function __construct(
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
        $type     = $this->configuration->getVersionType($provider);
        $version  = $this->configuration->getVersion($provider);
        $format   = $this->configuration->getVersionHeaderFormat($provider);

        if ( $type === 'header') 
        {
            $this->headers->set('API-Version', $version);
            $this->headers->set('Accept', $format);
        }

        return $this;
    }

}