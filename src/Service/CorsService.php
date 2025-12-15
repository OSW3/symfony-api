<?php 
namespace OSW3\Api\Service;

final class CorsService 
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

    // CORS Configuration

    /**
     * Check if CORS is enabled
     * 
     * @return bool
     */
    public function isEnabled(): bool
    {
        $provider = $this->contextService->getProvider();
        return $this->options($provider)['cors']['enabled'] ?? false;
    }

    /**
     * Get the allowed origins for CORS
     * 
     * @return array
     */
    public function getOrigins(): array
    {
        $provider = $this->contextService->getProvider();
        return $this->options($provider)['cors']['origins'] ?? ['*'];
    }

    /**
     * Get the allowed methods for CORS
     * 
     * @return array
     */
    public function getMethods(): array
    {
        $provider = $this->contextService->getProvider();
        return $this->options($provider)['cors']['methods'] ?? ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
    }

    /**
     * Get the allowed headers for CORS
     * 
     * @return array
     */
    public function getHeaders(): array
    {
        $provider = $this->contextService->getProvider();
        return $this->options($provider)['cors']['headers'] ?? [];
    }

    /**
     * Get the exposed headers for CORS
     * 
     * @return array
     */
    public function getExposedHeaders(): array
    {
        $provider = $this->contextService->getProvider();
        return $this->options($provider)['cors']['expose'] ?? [];
    }

    /**
     * Check if credentials are allowed for CORS
     * 
     * @return bool
     */
    public function exposeCredentials(): bool
    {
        $provider = $this->contextService->getProvider();
        return $this->options($provider)['cors']['credentials'] ?? false;
    }

    /**
     * Get the max age for CORS preflight requests
     * 
     * @return int
     */
    public function getMaxAge(): int
    {
        $provider = $this->contextService->getProvider();
        return $this->options($provider)['cors']['max_age'] ?? 3600;
    }
}