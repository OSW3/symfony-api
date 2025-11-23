<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Enum\Deprecation\State;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\ConfigurationService;

final class DeprecationService
{
    private ?bool $isEnabledCache = null;
    private ?\DateTimeImmutable $startDateCache = null;
    private ?\DateTimeImmutable $sunsetDateCache = null;
    private ?string $linkCache = null;
    private ?string $successorCache = null;
    private ?string $messageCache = null;

    public function __construct(
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
    ) {}

    
    // Deprecation data from ConfigurationService

    /**
     * Deprecation is enabled
     * 
     * @return bool
     */
    public function isEnabled(): bool
    {
        if ($this->isEnabledCache !== null) {
            return $this->isEnabledCache;
        }

        $this->isEnabledCache = $this->configurationService->isDeprecationEnabled(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );

        return $this->isEnabledCache;
    }

    /**
     * Get the deprecation start date
     * 
     * @return \DateTimeImmutable|null
     */
    public function getStartAt(): ?\DateTimeImmutable
    {
        if ($this->startDateCache !== null) {
            return $this->startDateCache;
        }

        $date = $this->configurationService->getDeprecationStartAt(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );

        $this->startDateCache = $date ? new \DateTimeImmutable($date) : null;

        return $this->startDateCache;
    }

    /**
     * Get the deprecation sunset date
     * 
     * @return \DateTimeImmutable|null
     */
    public function getSunsetAt(): ?\DateTimeImmutable
    {
        if ($this->sunsetDateCache !== null) {
            return $this->sunsetDateCache;
        }

        $date = $this->configurationService->getDeprecationSunsetAt(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );

        $this->sunsetDateCache = $date ? new \DateTimeImmutable($date) : null;

        return $this->sunsetDateCache;
    }

    /**
     * Get the deprecation link
     * 
     * @return string|null
     */
    public function getLink(): ?string
    {
        if ($this->linkCache !== null) 
        {
            return $this->linkCache;
        }
        
        $this->linkCache = $this->configurationService->getDeprecationLink(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );
        
        return $this->linkCache;
    }

    /**
     * Get the deprecation successor link
     * 
     * @return string|null
     */
    public function getSuccessor(): ?string
    {
        if ($this->successorCache !== null) {
            return $this->successorCache;
        }

        $this->successorCache = $this->configurationService->getDeprecationSuccessor(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );
        
        return $this->successorCache;
    }

    /**
     * Get the deprecation message
     * 
     * @return string|null
     */
    public function getMessage(): ?string
    {
        if ($this->messageCache !== null) {
            return $this->messageCache;
        }

        $this->messageCache = $this->configurationService->getDeprecationMessage(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );
        
        return $this->messageCache;
    }


    // Computed Deprecation State

    /**
     * API is active
     * 
     * An API is considered active if:
     * - Deprecation is enabled
     * - It is not deprecated
     * - It is not removed
     * 
     * @return bool
     */
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

    /**
     * API is removed
     * 
     * An API is considered removed if:
     * - Deprecation is enabled
     * - The current date is after the sunset date (if provided)
     * 
     * @return bool
     */
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

    /**
     * Get the current deprecation state
     * 
     * @return string One of 'active', 'deprecated', 'removed'
     */
    public function getState(): string
    {
        if ($this->isRemoved()) {
            return State::REMOVED->value;
        }

        if ($this->isDeprecated()) {
            return State::DEPRECATED->value;
        }

        return State::ACTIVE->value;
    }
}