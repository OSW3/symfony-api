<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\ConfigurationService;

final class UrlSupportService 
{
    public function __construct(
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
    ){}

    /**
     * Check if URL support is enabled for the given provider
     * 
     * @param string $provider
     * @return bool
     */
    public function isEnabled(): bool 
    {
        return $this->configurationService->hasUrlSupport(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
        );
    }

    /**
     * Check if URLs should be absolute
     * 
     * @return bool|null
     */
    public function isAbsolute(): ?bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        return $this->configurationService->isUrlAbsolute(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
        );
    }

    /**
     * Get the property to use for URL generation
     * 
     * @return string|null
     */
    public function getProperty(): ?string
    {
        if (!$this->isEnabled()) {
            return null;
        }

        return $this->configurationService->getUrlProperty(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
        );
    }
}