<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ContextService;

final class UrlSupportService 
{
    public function __construct(
        private readonly ContextService $contextService,
        private readonly ProviderService $providerService,
        private readonly CollectionService $collectionService,
    ){}

    /**
     * Get URL support options for the given provider/segment/collection
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @return array
     */
    private function options(?string $provider, ?string $segment, ?string $collection = null): array
    {
        if (! $this->providerService->exists($provider)) {
            return [];
        }

        if ($collection) {
            $collectionOptions = $this->collectionService->get($provider, $segment, $collection);
            if ($collectionOptions && isset($collectionOptions['url_support'])) {
                return $collectionOptions['url_support'];
            }
        }

        $providerOptions = $this->providerService->get($provider);
        return $providerOptions['url_support'] ?? [];
    }


    // -- CONFIG OPTIONS GETTERS

    /**
     * Check if URL support is enabled for the given provider
     * 
     * @param string $provider
     * @return bool
     */
    public function isEnabled(?string $provider = null, ?string $segment = null, ?string $collection = null, bool $fallbackOnCurrentContext = true): bool 
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
        }

        return $this->options(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
        )['enabled'] ?? false;
    }

    /**
     * Check if URLs should be absolute
     * 
     * @return bool|null
     */
    public function isAbsolute(?string $provider = null, ?string $segment = null, ?string $collection = null, bool $fallbackOnCurrentContext = true): ?bool
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
        }

        return $this->options(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
        )['absolute'] ?? true;
    }

    /**
     * Get the property to use for URL generation
     * 
     * @return string|null
     */
    public function getProperty(?string $provider = null, ?string $segment = null, ?string $collection = null, bool $fallbackOnCurrentContext = true): ?string
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
        }
        
        return $this->options(
            provider   : $provider,
            segment    : $segment,
            collection : $collection,
        )['property'] ?? 'url';
    }
}