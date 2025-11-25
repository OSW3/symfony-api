<?php 
namespace OSW3\Api\Service;

final class CacheControlService
{
    public function __construct(
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configuration,
    ){}

    // Cache Control Configuration

    /**
     * Check if cache control is enabled
     * 
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->configuration->isCacheControlEnabled(
            provider: $this->contextService->getProvider()
        );
    }

    /**
     * Check if cache control is public
     * 
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->configuration->isCacheControlPublic(
            provider: $this->contextService->getProvider()
        );
    }

    /**
     * Check if cache control is no-store
     * 
     * @return bool
     */
    public function isNoStore(): bool
    {
        return $this->configuration->isCacheControlNoStore(
            provider: $this->contextService->getProvider()
        );
    }

    /**
     * Check if cache control is must-revalidate
     * 
     * @return bool
     */
    public function isMustRevalidate(): bool
    {
        return $this->configuration->isCacheControlMustRevalidate(
            provider: $this->contextService->getProvider()
        );
    }

    /**
     * Get the max-age value
     * 
     * @return int|null
     */
    public function getMaxAge(): ?int
    {
        return $this->configuration->getCacheControlMaxAge(
            provider: $this->contextService->getProvider()
        );
    }

    // Computed Cache Control Values

    /**
     * Convert cache control directives to a string
     * 
     * @return string
     */
    public function toString(): string
    {
        $directives = [];

        if ($this->isPublic()) {
            $directives[] = 'public';
        } else {
            $directives[] = 'private';
        }

        if ($this->isNoStore()) {
            $directives[] = 'no-store';
        }

        if ($this->isMustRevalidate()) {
            $directives[] = 'must-revalidate';
        }

        $maxAge = $this->getMaxAge();
        if ($maxAge !== null) {
            $directives[] = "max-age={$maxAge}";
        }

        return implode(', ', $directives);
    }
}