<?php 
namespace OSW3\Api\Service;

final class ApiService 
{
    public function __construct(
        private readonly ConfigurationService $configuration,
    ){}

    /**
     * Get the version number of the API from configuration
     * 
     * @return int
     */
    public function getVersionNumber(): int
    {
        $provider = $this->configuration->guessProvider();
        return $this->configuration->getVersionNumber($provider);
    }

    /**
     * Get the version prefix of the API from configuration
     * 
     * @return string
     */
    public function getVersionPrefix(): string
    {
        $provider = $this->configuration->guessProvider();
        return $this->configuration->getVersionPrefix($provider);
    }

    /**
     * Get all available API versions
     * 
     * @return array
     */
    public function getAllVersions(): array 
    {
        $providers = $this->configuration->getAllProviders();
        $versions = [];

        foreach($providers as $provider => $options) 
        {
            if ($this->isDeprecated($provider)) {
                continue;
            }

            array_push($versions, $this->getFullVersion($provider));
        }

        return $versions;
    }

    /**
     * Get the full version string for a specific API provider
     * 
     * @param string|null $provider
     * @return string
     */
    public function getFullVersion(?string $provider = null): string 
    {
        $provider = $provider ?? $this->configuration->guessProvider();
        $prefix   = $this->configuration->getVersionPrefix($provider);
        $number   = $this->configuration->getVersionNumber($provider);

        return "{$prefix}{$number}";
    }

    /**
     * Check if the API provider is deprecated
     * 
     * @return bool
     */
    public function isDeprecated(): bool
    {
        $provider = $this->configuration->guessProvider();
        return $this->configuration->isDeprecated($provider);
    }
}