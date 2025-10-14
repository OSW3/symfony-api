<?php 
namespace OSW3\Api\Service;

final class DocumentationService 
{
    public function __construct(
        private readonly ConfigurationService $configuration,
        private readonly RequestService $request,
    ){}

    public function getUrl(string $providerName): string
    {
        $provider = $this->configuration->getProvider($providerName);
        $prefix   = $provider['documentation']['prefix'] ?? '';
        $base     = $this->request->getBase();
        
        return "{$base}/{$prefix}";
    }
}