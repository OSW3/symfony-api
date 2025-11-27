<?php 
namespace OSW3\Api\Service;

final class MetaService
{
    public function __construct(
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
    ) {}

    public function getAll(): array
    {
        return $this->configurationService->getAllMetadata(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );
    }

    public function getTitle(): ?string
    {
        return $this->getAll()['title'] ?? null;
    }
    
    public function getDescription(): ?string
    {
        return $this->getAll()['description'] ?? null;
    }
}