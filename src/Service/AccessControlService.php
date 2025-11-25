<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\ConfigurationService;

final class AccessControlService
{
    public function __construct(
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
    ){}

    // Access Control configuration

    /**
     * Get the merge strategy for access control roles
     * 
     * @return string
     */
    public function getStrategy(): string
    {
        return $this->configurationService->getAccessControlMergeStrategy(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );
    }
    
    /**
     * Get allowed roles for the current context
     * 
     * @return array<string>
     */
    public function getContextAllowedRoles(): array
    {
        return $this->configurationService->getAccessControlRoles(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );
    }

    /**
     * Get allowed roles for the given parameters
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return array<string>
     */
    public function getAllowedRoles(?string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): array
    {
        return $this->configurationService->getAccessControlRoles(
            provider  : $provider,
            segment   : $segment,
            collection: $collection,
            endpoint  : $endpoint,
        );
    }

    /**
     * Get the voter setting for access control
     * 
     * @return bool
     */
    public function getVoter(): ?string
    {
        return $this->configurationService->getAccessControlVoter(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );
    }


    // Computed access control

    public function isPublicAccessAllowed(): bool
    {
        $allowedRoles = $this->getContextAllowedRoles();
        return in_array('PUBLIC_ACCESS', $allowedRoles, true);
    }
}