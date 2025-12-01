<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\ConfigurationService;

final class AuthenticationService 
{
    public function __construct(
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
    ){}



    public function getName(): string 
    {
        return $this->configurationService->getAuthenticationName(
            provider  : $this->contextService->getProvider(),
            collection: $this->contextService->getCollection()
        );
    }



    public function isEnabled(): bool 
    {
        return $this->configurationService->isAuthenticationEndpointEnabled(
            provider  : $this->contextService->getProvider(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint()
        );
    }

    public function getPath(): string 
    {
        return $this->configurationService->getAuthenticationEndpointPath(
            provider  : $this->contextService->getProvider(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint()
        );
    }

    public function getPrefix(): string 
    {
        return $this->configurationService->getAuthenticationRoutePrefix(
            provider  : $this->contextService->getProvider(),
            collection: $this->contextService->getCollection()
        );
    }

    public function getHosts(): array 
    {
        return $this->configurationService->getAuthenticationEndpointHosts(
            provider  : $this->contextService->getProvider(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint()
        );
    }

    public function getSchemes(): array 
    {
        return $this->configurationService->getAuthenticationEndpointSchemes(
            provider  : $this->contextService->getProvider(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint()
        );
    }
    
    public function getController(): ?string 
    {
        return $this->configurationService->getAuthenticationEndpointController(
            provider  : $this->contextService->getProvider(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint()
        );
    }

    public function getProperties(): array 
    {
        return $this->configurationService->getAuthenticationEndpointProperties(
            provider  : $this->contextService->getProvider(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint()
        );
    }
}