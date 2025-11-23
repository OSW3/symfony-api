<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ContextService;

final class VersionService 
{
    private ?int $numberCache = null;
    private ?string $prefixCache = null;
    private ?string $locationCache = null;
    private ?string $headerDirectiveCache = null;
    private ?string $headerPatternCache = null;
    private ?string $labelCache = null;
    private ?bool $betaCache = null;
    private ?bool $deprecatedCache = null;
    private ?array $allVersionsCache = null;
    private ?array $supportedVersionsCache = null;
    private ?array $deprecatedVersionsCache = null;
    
    public function __construct(
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
    ){}

    // Version data from ConfigurationService

    /**
     * Get the version number of the API from configuration
     * 
     * @return int
     */
    public function getNumber(): int
    {
        if ($this->numberCache !== null) {
            return $this->numberCache;
        }

        $this->numberCache = $this->configurationService->getVersionNumber(
            provider: $this->contextService->getProvider(),
        );

        return $this->numberCache;
    }

    /**
     * Get the version prefix of the API from configuration
     * 
     * @return string
     */
    public function getPrefix(): string
    {
        if ($this->prefixCache !== null) {
            return $this->prefixCache;
        }

        $this->prefixCache = $this->configurationService->getVersionPrefix(
            provider: $this->contextService->getProvider()
        );
        
        return $this->prefixCache;
    }

    /**
     * Get the version location header value
     * 
     * @return string
     */
    public function getLocation(): string 
    {
        if ($this->locationCache !== null) {
            return $this->locationCache;
        }

        $this->locationCache = $this->configurationService->getVersionLocation(
            provider: $this->contextService->getProvider()
        );

        return $this->locationCache;
    }

    /**
     * Get the version header directive
     * 
     * @return string
     */
    public function getHeaderDirective(): string 
    {
        if ($this->headerDirectiveCache !== null) {
            return $this->headerDirectiveCache;
        }

        $this->headerDirectiveCache = $this->configurationService->getVersionHeaderDirective(
            provider: $this->contextService->getProvider()
        );

        return $this->headerDirectiveCache;
    }

    /**
     * Get the version header pattern
     * 
     * @return string
     */
    public function getHeaderPattern(): string 
    {
        if ($this->headerPatternCache !== null) {
            return $this->headerPatternCache;
        }
        $this->headerPatternCache = $this->configurationService->getVersionHeaderPattern(
            provider: $this->contextService->getProvider()
        );

        return $this->headerPatternCache;
    }

    /**
     * Check if the API provider is beta
     * 
     * @return bool
     */
    public function isBeta(?string $provider = null): bool
    {
        if ($this->betaCache !== null && !$provider) {
            return $this->betaCache;
        }

        if (!$provider || !$this->configurationService->hasProvider($provider)) {
            $provider = $this->contextService->getProvider();
        }

        $this->betaCache = $this->configurationService->isVersionBeta($provider);

        return $this->betaCache;
    }

    /**
     * Check if the API provider is deprecated
     * e.g.: $this->isDeprecated('my_custom_api_v1', 'collections');
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @return bool
     */
    public function isDeprecated(?string $provider = null, string $segment = ContextService::SEGMENT_COLLECTION): bool
    {
        if ($this->deprecatedCache !== null && !$provider) {
            return $this->deprecatedCache;
        }

        if (!$provider || !$this->configurationService->hasProvider($provider)) {
            $provider = $this->contextService->getProvider();
        }

        $this->deprecatedCache = $this->configurationService->isDeprecationEnabled($provider, $segment);

        return $this->deprecatedCache;
    }


    // Computed Version Information

    /**
     * Get the full version string for a specific API provider
     * 
     * @param string|null $provider
     * @return string
     */
    public function getLabel(?string $provider = null): string 
    {
        if ($this->labelCache !== null && !$provider) {
            return $this->labelCache;
        }

        if (!$provider || !$this->configurationService->hasProvider($provider)) {
            $provider = $this->contextService->getProvider();
        }

        $prefix   = $this->configurationService->getVersionPrefix($provider);
        $number   = $this->configurationService->getVersionNumber($provider);
        $beta     = $this->isBeta($provider);
        
        $label = $prefix;
        $label .= $number;
        $label .= $beta ? '-beta' : '';

        $this->labelCache = $label;

        return $this->labelCache;
    }

    /**
     * Get all available API versions
     * 
     * @return array<string>
     */
    public function getAllVersions(): array 
    {
        if ($this->allVersionsCache !== null) {
            return $this->allVersionsCache;
        }

        $versions = [];
        $providers = $this->configurationService->getProviderNames();

        foreach($providers as $provider) {
            array_push($versions, $this->getLabel($provider));
        }

        $this->allVersionsCache = $versions;

        return $this->allVersionsCache;
    }

    /**
     * Get all supported API versions
     * 
     * @return array<string>
     */
    public function getSupportedVersions(): array 
    {
        if ($this->supportedVersionsCache !== null) {
            return $this->supportedVersionsCache;
        }

        $versions = [];
        $providers = $this->configurationService->getProviderNames();

        foreach($providers as $provider) {
            if ($this->isDeprecated($provider)) {
                continue;
            }

            array_push($versions, $this->getLabel($provider));
        }
        $this->supportedVersionsCache = $versions;

        return $this->supportedVersionsCache;
    }

    /**
     * Get all deprecated API versions
     * 
     * @return array<string>
     */
    public function getDeprecatedVersions(): array 
    {
        if ($this->deprecatedVersionsCache !== null) {
            return $this->deprecatedVersionsCache;
        }

        $versions = [];
        $providers = $this->configurationService->getProviderNames();

        foreach($providers as $provider) {
            if ($this->isDeprecated($provider)) {
                array_push($versions, $this->getLabel($provider));
            }

        }

        $this->deprecatedVersionsCache = $versions;

        return $this->deprecatedVersionsCache;
    }
}