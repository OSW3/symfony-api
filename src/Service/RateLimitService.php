<?php
namespace OSW3\Api\Service;

use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\EndpointService;
use OSW3\Api\Service\ProviderService;
use OSW3\Api\Service\CollectionService;

final class RateLimitService
{
    private const TIME_UNITS = [
        'second' => 1,          // 1 second
        'minute' => 60,         // 60 seconds
        'hour'   => 3600,       // 60 minutes
        'day'    => 86400,      // 24 hours
        'week'   => 604800,     // 7 days
        'month'  => 2592000,    // 30 days
        'year'   => 31536000,   // 365 days
    ];

    private ?array $limitCache = null;

    public function __construct(
        private readonly ContextService $contextService,
        private readonly ProviderService $providerService,
        private readonly CollectionService $collectionService,
        private readonly EndpointService $endpointService,
    ) {}

    /**
     * Get rate limit options for the given provider/segment/collection/endpoint
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

        // 1. Endpoint-specific rate limit
        if ($collection && $endpoint) {
            $endpointOptions = $this->endpointService->get($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['rate_limit'])) {
                return $endpointOptions['rate_limit'];
            }
        }

        // 2. Collection-level rate limit
        if ($collection) {
            $collectionOptions = $this->collectionService->get($provider, $segment, $collection);
            if ($collectionOptions && isset($collectionOptions['rate_limit'])) {
                return $collectionOptions['rate_limit'];
            }
        }

        // 3. Global default rate limit
        $providerOptions = $this->providerService->get($provider);
        return $providerOptions['rate_limit'] ?? [];
    }


    // -- CONFIG OPTIONS GETTERS
    
    /**
     * Check if rate limiting is enabled
     * 
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
     * Get the default rate limit value
     * 
     * @return string
     */
    public function getDefaultLimit(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): string
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
        )['limit'] ?? '100/hour';
    }
    
    /**
     * Get the rate limit configuration by role
     * 
     * @return array<string, string>
     */
    public function getLimitByRole(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): array
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
        )['by_role'] ?? [];
    }

    /**
     * Get the rate limit configuration by user
     * 
     * @return array<string, string>
     */
    public function getLimitByUser(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): array
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
        )['by_user'] ?? [];
    }

    /**
     * Get the rate limit configuration by IP
     * 
     * @return array<string, string>
     */
    public function getLimitByIp(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): array
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
        )['by_ip'] ?? [];
    }

    /**
     * Get the rate limit configuration by application
     * 
     * @return array<string, string>
     */
    public function getLimitByApplication(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): array
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
        )['by_application'] ?? [];
    }


    // -- COMPUTED GETTERS

    /**
     * Get the rate limit configuration
     * 
     * @return array{requests: int, period: string} Array with 'requests' and 'period' keys
     */
    public function getLimit(): array
    {
        if ($this->limitCache !== null) {
            return $this->limitCache;
        }

        $fallback = [
            'requests' => 0,
            'period'   => self::TIME_UNITS['minute'],
        ];

        if (!$this->isEnabled()) {
            $this->limitCache = $fallback;
            return $this->limitCache;
        }

        $limit       = explode('/', $this->getDefaultLimit());

        $requests    = (int) ($limit[0] ?? 0);
        $periodName  = (string) ($limit[1] ?? null);
        $periodValue = self::TIME_UNITS[$periodName] ?? null;

        if ($requests <= 0 || !$periodValue) {
            $this->limitCache = $fallback;
            return $this->limitCache;
        }

        $this->limitCache = [
            'requests' => $requests,
            'period'   => $periodValue,
        ];

        return $this->limitCache;
    }

    /**
     * Get the number of requests allowed
     * 
     * @return int
     */
    public function getRequestsLimit(): int
    {
        $limit = $this->getLimit();
        return $limit['requests'];
    }

    /**
     * Get the time period for the rate limit in seconds
     * 
     * @return int
     */
    public function getPeriodLimit(): int
    {
        $limit = $this->getLimit();
        return $limit['period'];
    }

    /**
     * Get the number of used requests
     * 
     * @return int|false Number of used requests or false if rate limiting is disabled
     */
    public function getUsed(): int|false
    {
        if (!$this->isEnabled()) {
            return false;
        }

        // Placeholder implementation; replace with actual logic to determine used requests
        // TODO: Get used requests in data store (e.g., database, cache, etc.)
        return 42; // Example: 42 requests used
    }

    /**
     * Get the number of remaining requests
     * 
     * @return int|false Number of remaining requests or false if rate limiting is disabled
     */
    public function getRemaining(): int|false
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $limit     = $this->getLimit();
        $requests  = $limit['requests'];
        $used      = $this->getUsed();

        if ($used === false) {
            return false;
        }

        return (int) $requests - $used;;
    }

    /**
     * Get the reset time for the rate limit
     * 
     * @return int|false Timestamp of when the rate limit resets or false if rate limiting is disabled
     */
    public function getReset(): int|false
    {
        if (!$this->isEnabled()) {
            return false;
        }

        // Placeholder implementation; replace with actual logic to determine reset time
        // TODO: Get reset time in data store (e.g., database, cache, etc.)
        return time() + 600; // Example: resets in 10 minutes
    }
}