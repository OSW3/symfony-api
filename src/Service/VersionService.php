<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\DeprecationService;
use OSW3\Api\DependencyInjection\Configuration;
use OSW3\Api\Enum\Version\Location;
use OSW3\Api\Enum\Version\Mode;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class VersionService 
{
    private readonly array $configuration;
    private ?array $allVersions = null;
    private ?array $supportedVersions = null;
    private ?array $deprecatedVersions = null;
    
    public function __construct(
        #[Autowire(service: 'service_container')] 
        private readonly ContainerInterface $container,
        private readonly ContextService $contextService,
        private readonly ProviderService $providerService,
        private readonly DeprecationService $deprecationService,
    ){
        $this->configuration = $container->getParameter(Configuration::NAME);
    }


    // -- CONFIG OPTIONS GETTERS

    /**
     * Get the versioning mode
     * 
     * @return string
     */
    public function getMode(): string
    {
        return $this->configuration['versioning']['mode'] ?? Mode::AUTO->value;
    }

    /**
     * Get the version number for a specific API provider
     * 
     * @param string|null $provider
     * @return int|null
     */
    public function getNumber(?string $provider = null): ?int
    {
        // Get current provider if $provider is not defined
        $provider ??= $this->contextService->getProvider();

        // Return null if provider does not exist
        return $this->providerService->get($provider)['version']['number'] ?? null;
    }

    /**
     * Get the version prefix for a specific API provider
     * 
     * @param string|null $provider
     * @return string|null
     */
    public function getPrefix(?string $provider = null): ?string
    {
        // Get current provider if $provider is not defined
        $provider ??= $this->contextService->getProvider();
        
        // Return null if provider does not exist
        // -> provider version prefix
        // -> global version prefix
        // -> null
        return $this->providerService->get($provider)['version']['prefix'] 
            ?? $this->configuration['versioning']['prefix']
            ?? null
        ;
    }

    /**
     * Get the version location for a specific API provider
     * 
     * @param string|null $provider
     * @return string|null
     */
    public function getLocation(?string $provider = null): string 
    {
        // Get current provider if $provider is not defined
        $provider ??= $this->contextService->getProvider();

        // Return null if provider does not exist
        // -> provider version location
        // -> global version location
        // -> Location::PATH
        return $this->providerService->get($provider)['version']['location'] 
            ?? $this->configuration['versioning']['location']
            ?? Location::PATH->value
        ;
    }

    /**
     * Get the version directive header for a specific API provider
     * 
     * @param string|null $provider
     * @return string|null
     */
    public function getDirective(?string $provider = null): string 
    {
        // Get current provider if $provider is not defined
        $provider ??= $this->contextService->getProvider();

        // Return "Accept" if provider does not exist
        // -> provider version directive
        // -> "Accept" (default)
        return $this->providerService->get($provider)['version']['directive'] 
            ?? 'Accept'
        ;
    }

    /**
     * Get the version directive pattern for a specific API provider
     * 
     * @param string|null $provider
     * @return string|null
     */
    public function getPattern(?string $provider = null): ?string 
    {
        // Get current provider if $provider is not defined
        $provider ??= $this->contextService->getProvider();

        // Return null if provider does not exist
        // FIX: Prevent the null coalescing operator from returning an error
        // -> provider version pattern
        // -> null
        return $this->providerService->get($provider)['version']['pattern'] 
            ?? null
        ;
    }

    /**
     * Check if a specific API provider is in beta
     * 
     * @param string|null $provider
     * @return bool
     */
    public function isBeta(?string $provider = null): bool
    {
        // Get current provider if $provider is not defined
        $provider ??= $this->contextService->getProvider();

        // Return false if provider does not exist
        // -> provider version beta
        // -> false
        return $this->providerService->get($provider)['version']['beta'] 
            ?? false
        ;
    }


    // -- COMPUTED GETTERS

    /**
     * Get the full version string for a specific API provider
     * 
     * @param string|null $provider
     * @return string
     */
    public function getLabel(?string $provider = null): string 
    {
        $provider ??= $this->contextService->getProvider();
        $prefix     = $this->getPrefix();
        $number     = $this->getNumber($provider);
        $beta       = $this->isBeta($provider);
        $label      = $prefix;
        $label     .= $number;
        $label     .= $beta ? '-beta' : '';
        
        return $label;
    }

    /**
     * Check if a specific API provider is deprecated
     * 
     * @param string|null $provider
     * @param string $segment
     * @return bool
     */
    public function isDeprecated(?string $provider = null, string $segment = ContextService::SEGMENT_COLLECTION): bool
    {
        // Get current provider if $provider is not defined
        $provider ??= $this->contextService->getProvider();

        if (!$this->providerService->exists($provider)) {
            return false;
        }

        return $this->deprecationService->isEnabled(
            provider: $provider, 
            segment: $segment
        );
    }

    /**
     * Get all available API versions
     * 
     * @return array<string>
     */
    public function getAllVersions(): array 
    {
        if ($this->allVersions !== null) {
            return $this->allVersions;
        }

        $versions = [];
        $providers = $this->providerService->names();

        foreach($providers as $provider) {
            array_push($versions, $this->getLabel($provider));

        }

        $this->allVersions = $versions;

        return $this->allVersions;
    }

    /**
     * Get all supported API versions
     * 
     * @return array<string>
     */
    public function getSupportedVersions(): array 
    {
        if ($this->supportedVersions !== null) {
            return $this->supportedVersions;
        }

        $versions = [];
        $providers = $this->providerService->names();

        foreach($providers as $provider) {
            if ($this->isDeprecated($provider)) {
                continue;
            }

            array_push($versions, $this->getLabel($provider));
        }
        $this->supportedVersions = $versions;

        return $this->supportedVersions;
    }

    /**
     * Get all deprecated API versions
     * 
     * @return array<string>
     */
    public function getDeprecatedVersions(): array 
    {
        if ($this->deprecatedVersions !== null) {
            return $this->deprecatedVersions;
        }

        $versions = [];
        $providers = $this->providerService->names();

        foreach($providers as $provider) {
            if ($this->isDeprecated($provider)) {
                array_push($versions, $this->getLabel($provider));
            }

        }

        $this->deprecatedVersions = $versions;

        return $this->deprecatedVersions;
    }
}