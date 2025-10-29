<?php 
namespace OSW3\Api\Service;

final class VersionService 
{
    public function __construct(
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
    ){}

    /**
     * Get the version number of the API from configuration
     * 
     * @return int
     */
    public function getNumber(): int
    {
        $provider = $this->contextService->getProvider();
        return $this->configurationService->getVersionNumber($provider);
    }

    /**
     * Get the version prefix of the API from configuration
     * 
     * @return string
     */
    public function getPrefix(): string
    {
        $provider = $this->contextService->getProvider();
        return $this->configurationService->getVersionPrefix($provider);
    }

    /**
     * Get all available API versions
     * 
     * @return array<string>
     */
    public function getAllVersions(): array 
    {
        $versions = [];
        $providers = $this->configurationService->getProviders();

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
        $providers = $this->configurationService->getProviders();

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
        $providers = $this->configurationService->getProviders();

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
        if (!$provider || !$this->configurationService->hasProvider($provider)) {
            $provider = $this->contextService->getProvider();
        }

        $prefix   = $this->configurationService->getVersionPrefix($provider);
        $number   = $this->configurationService->getVersionNumber($provider);
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
        if (!$provider || !$this->configurationService->hasProvider($provider)) {
            $provider = $this->contextService->getProvider();
        }

        return $this->configurationService->isVersionBeta($provider);
    }

    /**
     * Check if the API provider is deprecated
     * 
     * @return bool
     */
    public function isDeprecated(?string $provider = null): bool
    {
        if (!$provider || !$this->configurationService->hasProvider($provider)) {
            $provider = $this->contextService->getProvider();
        }

        return $this->configurationService->isVersionDeprecated($provider);
    }
}