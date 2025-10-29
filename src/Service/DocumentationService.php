<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\ConfigurationService;

// TODO: DOCUMENTATION SERVICE

final class DocumentationService 
{
    public function __construct(
        private readonly RequestService $requestService,
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
    ){}

    public function isEnabled(): bool
    {
        $provider = $this->contextService->getProvider();
        return $this->configurationService->isDocumentationEnabled($provider);
    }

    public function getUrl(): string
    {
        return '';
        // $provider = $this->contextService->getProvider();
        // $provider = $this->configurationService->getProvider($provider);
        // $prefix   = $provider['documentation']['prefix'] ?? '';
        // $base     = $this->requestService->getBase();
        
        // return "{$base}/{$prefix}";
    }
}