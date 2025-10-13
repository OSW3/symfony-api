<?php 
namespace OSW3\Api\Service;

final class DocumentationService 
{
    public function __construct(
        private readonly ConfigurationService $configuration,
    ){}

    public function getUrl(string $providerName): string
    {
        $provider = $this->configuration->getProvider($providerName);
        $prefix  = $provider['documentation']['prefix'] ?? '';
        $baseUrl = $this->configuration->getBaseUrl();
        
        return "{$baseUrl}/{$prefix}";
    }
}