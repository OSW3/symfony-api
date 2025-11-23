<?php
namespace OSW3\Api\Service;

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

    public function __construct(
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
    ) {}

    public function isEnabled(): bool
    {
        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();

        return $this->configurationService->isRateLimitEnabled($provider, $collection, $endpoint);
    }


    // ──────────────────────────────
    // Configuration
    // ──────────────────────────────

    public function getDefaultLimit(): string
    {
        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();

        return $this->configurationService->getRateLimitValue($provider, $collection, $endpoint);
    }

    public function getLimitByRole(): array
    {
        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();

        return $this->configurationService->getRateLimitByRole($provider, $collection, $endpoint);
    }

    public function getLimitByUser(): array
    {
        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();

        return $this->configurationService->getRateLimitByUser($provider, $collection, $endpoint);
    }

    public function getLimitByIp(): array
    {
        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();

        return $this->configurationService->getRateLimitByIp($provider, $collection, $endpoint);
    }

    public function getLimitByApplication(): array
    {
        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();

        return $this->configurationService->getRateLimitByApplication($provider, $collection, $endpoint);
    }


    // ──────────────────────────────
    // Statistics
    // ──────────────────────────────

    /**
     * Get the rate limit configuration
     * 
     * @return array{requests: int, period: string} Array with 'requests' and 'period' keys
     */
    public function getLimit(): array
    {
        $fallback = [
            'requests' => 0,
            'period'   => self::TIME_UNITS['minute'],
        ];

        if (!$this->isEnabled()) {
            return $fallback;
        }

        $limit       = explode('/', $this->getDefaultLimit());

        $requests    = (int) ($limit[0] ?? 0);
        $periodName  = (string) ($limit[1] ?? null);
        $periodValue = self::TIME_UNITS[$periodName] ?? null;

        if ($requests <= 0 || !$periodValue) {
            return $fallback;
        }

        return [
            'requests' => $requests,
            'period'   => $periodValue,
        ];
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