<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\ConfigurationService;

final class CompressionService 
{
    public function __construct(
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
    ){}
    

    public function isEnabled(): bool 
    {
        return $this->configurationService->isCompressionEnabled(
            provider: $this->contextService->getProvider()
        );
    }

    public function getFormat(): string 
    {
        return $this->configurationService->getCompressionFormat(
            provider: $this->contextService->getProvider()
        );
    }

    public function getLevel(): int 
    {
        return $this->configurationService->getCompressionLevel(
            provider: $this->contextService->getProvider()
        );
    }
}