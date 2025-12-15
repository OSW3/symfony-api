<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ProviderService;

final class CollectionService 
{
    public function __construct(
        private readonly ProviderService $providerService,
    ){}

    /**
     * Get all configured collections for a provider and segment
     * 
     * @param string $provider
     * @param string $segment
     * @return array
     */
    public function all(?string $provider, ?string $segment): array
    {
        if (
            ! $this->providerService->exists($provider) || 
            ! $this->providerService->isEnabled($provider) ||
            ! $this->providerService->hasSegment($provider, $segment)
        ) return [];

        return $this->providerService->get($provider)[$segment] ?? [];
    }

    /**
     * Get a configured collection by name for a provider and segment
     * 
     * @param string $provider
     * @param string $segment
     * @param string $collection
     * @return array|null
     */
    public function get(?string $provider, ?string $segment, ?string $collection): array
    {
        if (
            ! $this->providerService->exists($provider) ||
            ! $this->providerService->isEnabled($provider)
        ) return [];

        return $this->all($provider, $segment)[$collection] ?? [];
    }
    
    /**
     * Check if a collection exists for a provider and segment
     * 
     * @param string $provider
     * @param string $segment
     * @param string $collection
     * @return bool
     */
    public function exists(?string $provider, ?string $segment, ?string $collection): bool
    {
        if (! $this->providerService->exists($provider)) {
            return false;
        }

        return array_key_exists($collection, $this->all($provider, $segment));
    }

    /**
     * Get the name of a collection for a provider and segment
     * 
     * @param string $provider
     * @param string $segment
     * @param string $collection
     * @return string|null
     */
    public function getName(?string $provider, ?string $segment, ?string $collection): string|null
    {
        return $this->get($provider, $segment, $collection)['name'] ?? null;
    }

    /**
     * Check if a collection is enabled for a provider and segment
     * 
     * @param string $provider
     * @param string $segment
     * @param string $collection
     * @return bool
     */
    public function isEnabled(?string $provider, ?string $segment, ?string $collection): bool
    {
        if (!$this->providerService->exists($provider)) {
            return false;
        }

        if (!$this->exists($provider, $segment, $collection)) {
            return false;
        }

        return $this->get($provider, $segment, $collection)['enabled'] ?? $this->providerService->isEnabled($provider);
    }
}