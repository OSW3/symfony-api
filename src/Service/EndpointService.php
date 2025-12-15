<?php 
namespace OSW3\Api\Service;

final class EndpointService
{

    public function __construct(
        private readonly ProviderService $providerService,
        private readonly CollectionService $collectionService,
    ){}

    /**
     * Get all configured endpoints for a provider, segment and collection
     * 
     * @param string $provider
     * @param string $segment
     * @param string $collection
     * @return array
     */
    public function all(?string $provider, ?string $segment, ?string $collection): array
    {
        if (!$this->collectionService->isEnabled($provider, $segment, $collection)) {
            return [];
        }

        return $this->collectionService->get($provider, $segment, $collection)['endpoints'] ?? [];
    }

    /**
     * Get a configured endpoint by name for a provider, segment and collection
     * 
     * @param string $provider
     * @param string $segment
     * @param string $collection
     * @param string $endpoint
     * @return array|null
     */
    public function get(string $provider, string $segment, string $collection, string $endpoint): ?array
    {
        return $this->all($provider, $segment, $collection)[$endpoint] ?? null;
    }

    /**
     * Check if an endpoint exists for a provider, segment and collection
     * 
     * @param string $provider
     * @param string $segment
     * @param string $collection
     * @param string $endpoint
     * @return bool
     */
    public function exists(string $provider, string $segment, string $collection, string $endpoint): bool
    {
        if (
            ! $this->providerService->exists($provider) || 
            ! $this->collectionService->exists($provider, $segment, $collection)
        ) return false;

        return array_key_exists($endpoint, $this->all($provider, $segment, $collection));
    }

    /**
     * Check if an endpoint is enabled for a provider, segment and collection
     * 
     * @param string $provider
     * @param string $segment
     * @param string $collection
     * @param string $endpoint
     * @return bool
     */
    public function isEnabled(string $provider, string $segment, string $collection, string $endpoint): bool
    {
        if (
            ! $this->providerService->exists($provider) ||
            ! $this->collectionService->exists($provider, $segment, $collection) ||
            ! $this->exists($provider, $segment, $collection, $endpoint)
        ) return false;

        return $this->get($provider, $segment, $collection, $endpoint)['enabled']
            ?? $this->collectionService->isEnabled($provider, $segment, $collection);
    }
}