<?php 
namespace OSW3\Api\Service;

final class CacheControlService
{
    public function __construct(
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configuration,
    ){}

    public function isEnabled(): bool
    {
        $provider = $this->contextService->getProvider();
        return $this->configuration->isCacheControlEnabled($provider);
    }

    public function isPublic(): bool
    {
        $provider = $this->contextService->getProvider();
        return $this->configuration->isCacheControlPublic($provider);
    }

    public function isNoStore(): bool
    {
        $provider = $this->contextService->getProvider();
        return $this->configuration->isCacheControlNoStore($provider);
    }

    public function isMustRevalidate(): bool
    {
        $provider = $this->contextService->getProvider();
        return $this->configuration->isCacheControlMustRevalidate($provider);
    }

    public function getMaxAge(): ?int
    {
        $provider = $this->contextService->getProvider();
        return $this->configuration->getCacheControlMaxAge($provider);
    }

    public function toString(): string
    {
        $directives = [];

        if ($this->isPublic()) {
            $directives[] = 'public';
        } else {
            $directives[] = 'private';
        }

        if ($this->isNoStore()) {
            $directives[] = 'no-store';
        }

        if ($this->isMustRevalidate()) {
            $directives[] = 'must-revalidate';
        }

        $maxAge = $this->getMaxAge();
        if ($maxAge !== null) {
            $directives[] = "max-age={$maxAge}";
        }

        return implode(', ', $directives);
    }
}