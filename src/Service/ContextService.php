<?php
namespace OSW3\Api\Service;

use OSW3\Api\Service\ConfigurationService;

final class ContextService
{
    public function __construct(
        private readonly ConfigurationService $configurationService,
    ){}

    /**
     * Get the current context part or full context array
     * 
     * @param string|null $part 'provider'|'collection'|'endpoint'
     * @return array|string|null
     */
    public function getContext(?string $part = null): array|string|null
    {
        $context = [
            'provider'   => $this->configurationService->getContext('provider'),
            'collection' => $this->configurationService->getContext('collection'),
            'endpoint'   => $this->configurationService->getContext('endpoint'),
        ];

        return $part ? $context[$part] ?? [] : $context;
    }

    /**
     * Get the current provider name
     * 
     * @return string
     */
    public function getProvider(): string
    {
        return $this->configurationService->getContext('provider');
    }

    /**
     * Get the current collection name
     * 
     * @return string
     */
    public function getCollection(): string
    {
        return $this->configurationService->getContext('collection');
    }

    /**
     * Get the current endpoint name
     * 
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->configurationService->getContext('endpoint');
    }
}