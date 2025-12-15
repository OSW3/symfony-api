<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Enum\Deprecation\State;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\EndpointService;
use OSW3\Api\Service\ProviderService;
use OSW3\Api\Service\CollectionService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DeprecationService
{
    public function __construct(
        private readonly ContextService $contextService,
        private readonly ProviderService $providerService,
        private readonly CollectionService $collectionService,
        private readonly EndpointService $endpointService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * Get the deprecation options for a provider, segment, collection or endpoint
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return array
     */
    private function options(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): array 
    {
        if (! $this->providerService->exists($provider)) {
            return [];
        }

        // 1. Provider-level deprecation
        $providerOptions = $this->providerService->get($provider);
        if (isset($providerOptions['deprecation'])) {
            return $providerOptions['deprecation'];
        }

        // 2. Collection-level deprecation
        if ($collection) {
            $collectionOptions = $this->collectionService->get($provider, $segment, $collection);
            if (isset($collectionOptions['deprecation'])) {
                return $collectionOptions['deprecation'];
            }
        }

        // 3. Endpoint-level deprecation
        if ($collection && $endpoint) {
            $endpointOptions = $this->endpointService->get($provider, $segment, $collection, $endpoint);
            if (isset($endpointOptions['deprecation'])) {
                return $endpointOptions['deprecation'];
            }
        }

        // 4. Default
        return [];
    }


    // -- CONFIG OPTIONS GETTERS

    /**
     * Check if deprecation is enabled for a provider, segment, collection or endpoint
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return bool
     */
    public function isEnabled(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): bool
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }
        
        return $this->options(
            provider  : $provider,
            segment   : $segment,
            collection: $collection,
            endpoint  : $endpoint,
        )['enabled'] ?? false;
    }

    /**
     * Get the deprecation start date for a provider, segment, collection or endpoint
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return \DateTimeImmutable|null
     */
    public function getStartAt(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): ?\DateTimeImmutable
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }
        
        $startAt = $this->options(
            provider  : $provider,
            segment   : $segment,
            collection: $collection,
            endpoint  : $endpoint,
        )['start_at'] ?? null;

        return $startAt ? new \DateTimeImmutable($startAt) : null;
    }

    /**
     * Get the deprecation removal date for a provider, segment, collection or endpoint
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return \DateTimeImmutable|null
     */
    public function getSunsetAt(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): ?\DateTimeImmutable
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }
        
        $sunsetAt = $this->options(
            provider  : $provider,
            segment   : $segment,
            collection: $collection,
            endpoint  : $endpoint,
        )['sunset_at'] ?? null;

        return $sunsetAt ? new \DateTimeImmutable($sunsetAt) : null;
    }

    /**
     * Get the deprecation link for a provider, segment, collection or endpoint
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return string|null
     */
    public function getLink(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): ?string
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        $link = $this->options(
            provider  : $provider,
            segment   : $segment,
            collection: $collection,
            endpoint  : $endpoint,
        )['link'] ?? null;

        return $link ? $this->resolveLink($link) : null;
    }

    /**
     * Get the deprecation successor link for a provider, segment, collection or endpoint
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return string|null
     */
    public function getSuccessor(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): ?string
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        $link = $this->options(
            provider  : $provider,
            segment   : $segment,
            collection: $collection,
            endpoint  : $endpoint,
        )['successor'] ?? null;

        return $link ? $this->resolveLink($link) : null;
    }

    /**
     * Get the deprecation message for a provider, segment, collection or endpoint
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return string|null
     */
    public function getMessage(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): ?string
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        return $this->options(
            provider  : $provider,
            segment   : $segment,
            collection: $collection,
            endpoint  : $endpoint,
        )['message'] ?? null;
    }


    // -- COMPUTED GETTERS

    /**
     * Check if a provider, segment, collection or endpoint is active
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return bool
     */
    public function isActive(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): bool
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        return !$this->isEnabled($provider, $segment, $collection, $endpoint) 
            && !$this->isRemoved($provider, $segment, $collection, $endpoint)
        ;
    }

    /**
     * Check if a provider, segment, collection or endpoint is deprecated
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return bool
     */
    public function isDeprecated(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): bool
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        if (!$this->isEnabled($provider, $segment, $collection, $endpoint) || $this->isRemoved($provider, $segment, $collection, $endpoint)) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $startDate = $this->getStartAt($provider, $segment, $collection, $endpoint);

        if ($startDate) {
            if ($startDate > $now) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a provider, segment, collection or endpoint is removed
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return bool
     */
    public function isRemoved(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): bool
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        if (!$this->isEnabled($provider, $segment, $collection, $endpoint)) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $sunsetDate = $this->getSunsetAt($provider, $segment, $collection, $endpoint);

        if ($sunsetDate) {
            if ($sunsetDate <= $now) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the deprecation state for a provider, segment, collection or endpoint
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return string
     */
    public function getState(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): string
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        if ($this->isRemoved($provider, $segment, $collection, $endpoint)) {
            return State::REMOVED->value;
        }

        if ($this->isDeprecated($provider, $segment, $collection, $endpoint)) {
            return State::DEPRECATED->value;
        }

        return State::ACTIVE->value;
    }

    /**
     * Resolve a deprecation link, generating a URL if it's a route name
     * 
     * @param string $input
     * @return string|null
     */
    private function resolveLink(string $input): ?string
    {
        if (preg_match('#^https?://#i', $input)) {
            return $input;
        }

        try {
            return $this->urlGenerator->generate(
                $input,
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        } catch (\Throwable $e) {
            // Route does not exist or requires parameters; return null
        }
        
        return null;
    }
}