<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ProviderService;

final class CacheControlService
{
    public function __construct(
        private readonly ContextService $contextService,
        private readonly ProviderService $providerService,
        // private readonly ConfigurationService $configuration,
    ){}

    /**
     * Get the response options for a specific provider
     * 
     * @param string|null $provider
     * @return array
     */
    private function options(?string $provider): array 
    {
        if (! $this->providerService->exists($provider)) {
            return [];
        }

        $providerOptions = $this->providerService->get($provider);
        return $providerOptions['response'] ?? [];
    }


    // Cache Control Configuration

    /**
     * Check if cache control is enabled
     * 
     * @return bool
     */
    public function isEnabled(): bool
    {
        $provider = $this->contextService->getProvider();

        return $this->options($provider)['cache_control']['enabled'] ?? false;
    }

    /**
     * Check if cache control is public
     * 
     * @return bool
     */
    public function isPublic(): bool
    {
        $provider = $this->contextService->getProvider();

        return $this->options($provider)['cache_control']['public'] ?? false;
    }

    /**
     * Check if cache control is no-store
     * 
     * @return bool
     */
    public function isNoStore(): bool
    {
        $provider = $this->contextService->getProvider();

        return $this->options($provider)['cache_control']['no_store'] ?? false;
    }

    /**
     * Check if cache control is must-revalidate
     * 
     * @return bool
     */
    public function isMustRevalidate(): bool
    {
        $provider = $this->contextService->getProvider();

        return $this->options($provider)['cache_control']['must_revalidate'] ?? false;
    }

    /**
     * Get the max-age value
     * 
     * @return int|null
     */
    public function getMaxAge(): ?int
    {
        $provider = $this->contextService->getProvider();

        return $this->options($provider)['cache_control']['max_age'] ?? null;
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