<?php 
namespace OSW3\Api\Service;

final class VersionService 
{
    public function __construct(
        private readonly ConfigurationService $configuration,
    ){}
    
    /**
     * Get the current API provider from configuration
     * 
     * @return string
     */
    private function getProvider(): string
    {
        return $this->configuration->getContext('provider');
    }

    /**
     * Get the version number of the API from configuration
     * 
     * @return int
     */
    public function getNumber(): int
    {
        return $this->configuration->getVersionNumber($this->getProvider());
    }

    /**
     * Get the version prefix of the API from configuration
     * 
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->configuration->getVersionPrefix($this->getProvider());
    }

    public function getAllVersions(): array 
    {
        $versions = [];
        $providers = $this->configuration->getProviders();

        foreach($providers as $provider => $options) 
        {
            array_push($versions, $this->getLabel($provider));
        }

        return $versions;
    }

    /**
     * Get all supported API versions
     * 
     * @return array<string>
     */
    public function getSupportedVersions(): array 
    {
        $versions = [];
        $providers = $this->configuration->getProviders();

        foreach($providers as $provider => $options) 
        {
            if ($this->isDeprecated($provider)) {
                continue;
            }

            array_push($versions, $this->getLabel($provider));
        }

        return $versions;
    }

    /**
     * Get all deprecated API versions
     * 
     * @return array<string>
     */
    public function getDeprecatedVersions(): array 
    {
        $versions = [];
        $providers = $this->configuration->getProviders();

        foreach($providers as $provider => $options) 
        {
            if ($this->isDeprecated($provider)) {
                array_push($versions, $this->getLabel($provider));
            }

        }

        return $versions;
    }

    /**
     * Get the full version string for a specific API provider
     * 
     * @param string|null $provider
     * @return string
     */
    public function getLabel(?string $provider = null): string 
    {
        if (!$provider || !$this->configuration->hasProvider($provider)) {
            $provider = $this->getProvider();
        }

        $prefix   = $this->configuration->getVersionPrefix($provider);
        $number   = $this->configuration->getVersionNumber($provider);
        $beta     = $this->isBeta($provider);
        
        return "{$prefix}{$number}" . ($beta ? '-beta' : '');
    }

    /**
     * Check if the API provider is beta
     * 
     * @return bool
     */
    public function isBeta(?string $provider = null): bool
    {
        if (!$provider || !$this->configuration->hasProvider($provider)) {
            $provider = $this->getProvider();
        }

        return $this->configuration->isVersionBeta($provider);
    }

    /**
     * Check if the API provider is deprecated
     * 
     * @return bool
     */
    public function isDeprecated(?string $provider = null): bool
    {
        if (!$provider || !$this->configuration->hasProvider($provider)) {
            $provider = $this->getProvider();
        }

        return $this->configuration->isVersionDeprecated($provider);
    }
}