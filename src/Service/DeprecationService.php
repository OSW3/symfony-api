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
        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();

        return $this->configurationService->isDeprecationEnabled($provider, $collection, $endpoint);
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();

        $date = $this->configurationService->getDeprecationStartAt(
            $provider,
            $collection,
            $endpoint
        );

        return $date ? new \DateTimeImmutable($date) : null;
    }

    public function getSunsetAt(): ?\DateTimeImmutable
    {
        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();

        $date = $this->configurationService->getDeprecationSunsetAt(
            $provider,
            $collection,
            $endpoint
        );

        return $date ? new \DateTimeImmutable($date) : null;
    }

    public function getLink(): ?string
    {
        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();

        return $this->configurationService->getDeprecationLink(
            $provider,
            $collection,
            $endpoint
        );
    }

    public function getSuccessor(): ?string
    {
        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();

        return $this->configurationService->getDeprecationSuccessor(
            $provider,
            $collection,
            $endpoint
        );
    }

    public function getReason(): ?string
    {
        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();

        return $this->configurationService->getDeprecationReason(
            $provider,
            $collection,
            $endpoint
        );
    }

    public function getMessage(): ?string
    {
        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();

        return $this->configurationService->getDeprecationMessage(
            $provider,
            $collection,
            $endpoint
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