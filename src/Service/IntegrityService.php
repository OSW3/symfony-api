<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\ConfigurationService;

final class IntegrityService 
{
    private array $hashCache = [];

    public function __construct(
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
    ){}

    public function isEnabled(): bool 
    {
        $provider = $this->contextService->getProvider();
        return $this->configurationService->isChecksumEnabled($provider);
    }

    public function getAlgorithm(): string 
    {
        $provider = $this->contextService->getProvider();
        return $this->configurationService->getChecksumAlgorithm($provider);
    }

    public function getHash(string $algorithm): string 
    {
        return $this->hashCache[$algorithm] ?? '';
    }

    public function computeHash(string $data, string $algorithm): string 
    {
        if (!isset($this->hashCache[$algorithm])) {
            $this->hashCache[$algorithm] = hash($algorithm, $data, false);
        }

        return $this->hashCache[$algorithm];
    }
}