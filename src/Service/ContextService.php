<?php
namespace OSW3\Api\Service;

use OSW3\Api\ApiBundle;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpKernel\KernelInterface;

final class ContextService
{
    const SEGMENT_AUTHENTICATION = 'authentication';
    const SEGMENT_COLLECTION     = 'collections';
    
    public function __construct(
        private readonly KernelInterface $kernel,
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
            'segment'    => $this->configurationService->getContext('segment'),
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
    public function getProvider(): ?string
    {
        return $this->configurationService->getContext('provider') ?? null;
    }

    /**
     * Get the current segment name
     * 
     * @return string
     */
    public function getSegment(): ?string
    {
        return $this->configurationService->getContext('segment') ?? null;
    }

    /**
     * Get the current collection name
     * 
     * @return string
     */
    public function getCollection(): ?string
    {
        return $this->configurationService->getContext('collection') ?? null;
    }

    /**
     * Get the current endpoint name
     * 
     * @return string
     */
    public function getEndpoint(): ?string
    {
        return $this->configurationService->getContext('endpoint') ?? null;
    }

    /**
     * Get the application environment
     * 
     * @return string
     */
    public function getEnvironment(): ?string
    {
        return $this->kernel->getEnvironment() ?? null;
    }

    /**
     * Check if the application is in debug mode
     * 
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->kernel->isDebug() ?? false;
    }

    /**
     * Get the bundle directory path
     * 
     * @return string
     */
    public function getBundleDir(): string
    {
        $bundleClass = ApiBundle::class;
        $bundleName  = (new \ReflectionClass($bundleClass))->getShortName();

        return $this->kernel->getBundle($bundleName)->getPath();
    }

    /**
     * Get the project directory path
     * 
     * @return string
     */
    public function getProjectDir(): string
    {
        return $this->kernel->getProjectDir();
    }
}