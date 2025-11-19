<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\Response;

final class DeprecationService
{
    public const HEADER_DEPRECATION = 'Deprecation';
    public const HEADER_SUNSET      = 'Sunset';
    public const HEADER_LINK        = 'Link';
    public const HEADER_MESSAGE     = 'X-Message';
    // public const HEADER_WARNING     = 'Warning';

    public function __construct(
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
    ) {}

    // Configuration 

    public function isEnabled(): bool
    {
        return $this->configurationService->isDeprecationEnabled(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        $date = $this->configurationService->getDeprecationStartAt(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );

        return $date ? new \DateTimeImmutable($date) : null;
    }

    public function getSunsetAt(): ?\DateTimeImmutable
    {
        $date = $this->configurationService->getDeprecationSunsetAt(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );

        return $date ? new \DateTimeImmutable($date) : null;
    }

    public function getLink(): ?string
    {
        return $this->configurationService->getDeprecationLink(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );
    }

    public function getSuccessor(): ?string
    {
        return $this->configurationService->getDeprecationSuccessor(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );
    }

    public function getMessage(): ?string
    {
        return $this->configurationService->getDeprecationMessage(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );
    }


    // State

    public function isActive(): bool
    {
        return !$this->isEnabled() && !$this->isRemoved();
    }

    /**
     * API is deprecated
     * 
     * An API is considered deprecated if:
     * - Deprecation is enabled
     * - It is not removed
     * - The current date is after the start date (if provided)
     * 
     * @return bool
     */
    public function isDeprecated(): bool
    {
        if (!$this->isEnabled() || $this->isRemoved()) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $startDate = $this->getStartAt();

        if ($startDate) {
            if ($startDate > $now) {
                return false;
            }
        }

        return true;
    }

    public function isRemoved(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $sunsetDate = $this->getSunsetAt();

        if ($sunsetDate) {
            if ($sunsetDate <= $now) {
                return true;
            }
        }

        return false;
    }

    public function getState(): string
    {
        if ($this->isRemoved()) {
            return 'removed';
        }

        if ($this->isDeprecated()) {
            return 'deprecated';
        }

        return 'active';
    }
}