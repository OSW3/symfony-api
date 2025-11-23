<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\ConfigurationService;

final class IntegrityService 
{
    private array $hashCache = [];
    private ?bool $enabledCache = null;
    private ?string $algorithmCache = null;

    public function __construct(
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
    ){}

    /**
     * Is integrity check enabled?
     * 
     * @return bool
     */
    public function isEnabled(): bool 
    {
        if ($this->enabledCache !== null) {
            return $this->enabledCache;
        }

        $this->enabledCache = $this->configurationService->isChecksumEnabled(
            provider: $this->contextService->getProvider()
        );

        return $this->enabledCache;
    }

    /**
     * Get the checksum algorithm
     * 
     * @return string
     */
    public function getAlgorithm(): string 
    {
        if ($this->algorithmCache !== null) {
            return $this->algorithmCache;
        }

        $this->algorithmCache = $this->configurationService->getChecksumAlgorithm(
            provider: $this->contextService->getProvider()
        );

        return $this->algorithmCache;
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