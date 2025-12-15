<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\ProviderService;
use OSW3\Api\Service\ConfigurationService;

final class IntegrityService 
{
    private array $hashCache = [];

    public function __construct(
        private readonly ContextService $contextService,
        private readonly ProviderService $providerService,
        // private readonly ConfigurationService $configurationService,
    ){}

    /**
     * Get the response options for a specific provider
     * 
     * @param string|null $provider
     * @return array
     */
    private function options(?string $provider): array 
    {
        if (! $this->providerService->exists($provider)) {
            return [];
        }

        $providerOptions = $this->providerService->get($provider);
        return $providerOptions['response'] ?? [];
    }


    // -- CONFIG OPTIONS GETTERS



    // -- COMPUTED GETTERS

    /**
     * Is integrity check enabled?
     * 
     * @return bool
     */
    public function isEnabled(): bool 
    {
        $provider = $this->contextService->getProvider();

        return $this->options($provider)['security']['checksum']['enabled'] ?? false;
    }

    /**
     * Get the checksum algorithm
     * 
     * @return string
     */
    public function getAlgorithm(): string 
    {
        $provider = $this->contextService->getProvider();
        
        return $this->options($provider)['security']['checksum']['algorithm'] ?? 'md5';
    }

    /**
     * Get the computed hash for the given algorithm
     * 
     * @param string $algorithm
     * @return string
     */
    public function getHash(string $algorithm): string 
    {
        return $this->hashCache[$algorithm] ?? '';
    }

    /**
     * Compute the hash for the given data and algorithm
     * 
     * @param string $data
     * @param string $algorithm
     * @return string
     */
    public function computeHash(string $data, string $algorithm): string 
    {
        if (!isset($this->hashCache[$algorithm])) {
            $this->hashCache[$algorithm] = hash($algorithm, $data, false);
        }

        return $this->hashCache[$algorithm];
    }
}