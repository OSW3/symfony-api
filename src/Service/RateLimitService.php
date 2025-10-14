<?php
namespace OSW3\Api\Service;

final class RateLimitService
{
    public function __construct(
        private readonly ConfigurationService $configuration,
    ) {}

    public function getLimit(string $providerName): int|false
    {
        if (!$this->configuration->isRateLimitEnabled($providerName)) {
            return false;
        }

        return $this->configuration->getRateLimit($providerName);
    }

    public function getRemaining(string $providerName): int|false
    {
        if (!$this->configuration->isRateLimitEnabled($providerName)) {
            return false;
        }

        $limit = $this->getLimit($providerName);
        $used  = 42; //$this->getUsed($providerName);

        return (int)($limit - $used);
    }

    public function getReset(string $providerName): int|false
    {
        if (!$this->configuration->isRateLimitEnabled($providerName)) {
            return false;
        }

        // Placeholder implementation; replace with actual logic to determine reset time
        return time() + 3600; // Example: resets in 1 hour
    }
}