<?php 
namespace OSW3\Api\Service;

final class CorsService 
{
    public function __construct(
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configuration,
    ){}

    // CORS Configuration

    /**
     * Check if CORS is enabled
     * 
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->configuration->isCorsEnabled(
            provider: $this->contextService->getProvider()
        );
    }

    /**
     * Get the allowed origins for CORS
     * 
     * @return array
     */
    public function getOrigins(): array
    {
        return $this->configuration->getCorsAllowedOrigins(
            provider: $this->contextService->getProvider()
        );
    }

    /**
     * Get the allowed methods for CORS
     * 
     * @return array
     */
    public function getMethods(): array
    {
        return $this->configuration->getCorsAllowedMethods(
            provider: $this->contextService->getProvider()
        );
    }

    /**
     * Get the allowed headers for CORS
     * 
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->configuration->getCorsHeaders(
            provider: $this->contextService->getProvider()
        );
    }

    /**
     * Get the exposed headers for CORS
     * 
     * @return array
     */
    public function getExposedHeaders(): array
    {
        return $this->configuration->getCorsExposedHeaders(
            provider: $this->contextService->getProvider()
        );
    }

    /**
     * Check if credentials are allowed for CORS
     * 
     * @return bool
     */
    public function exposeCredentials(): bool
    {
        return $this->configuration->getCorsCredentials(
            provider: $this->contextService->getProvider()
        );
    }

    /**
     * Get the max age for CORS preflight requests
     * 
     * @return int
     */
    public function getMaxAge(): int
    {
        return $this->configuration->getCorsMaxAge(
            provider: $this->contextService->getProvider()
        );
    }
}