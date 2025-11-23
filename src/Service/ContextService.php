<?php
namespace OSW3\Api\Service;

use OSW3\Api\ApiBundle;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpKernel\KernelInterface;

final class ContextService
{
    const SEGMENT_AUTHENTICATION = 'authentication';
    const SEGMENT_COLLECTION     = 'collections';

    private ?string $providerCache = null;
    private ?string $segmentCache = null;
    private ?string $collectionCache = null;
    private ?string $endpointCache = null;
    private ?string $environmentCache = null;
    private ?bool $debugCache = null;
    private ?string $bundleDirCache = null;
    private ?string $projectDirCache = null;
    
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
        if ($this->providerCache !== null) {
            return $this->providerCache;
        }
        
        $this->providerCache = $this->configurationService->getContext('provider') ?? null;
        
        return $this->providerCache;
    }

    /**
     * Get the current segment name
     * 
     * @return string
     */
    public function getSegment(): ?string
    {
        if ($this->segmentCache !== null) {
            return $this->segmentCache;
        }
        
        $this->segmentCache = $this->configurationService->getContext('segment') ?? null;
        
        return $this->segmentCache;
    }

    /**
     * Get the current collection name
     * 
     * @return string
     */
    public function getCollection(): ?string
    {
        if ($this->collectionCache !== null) {
            return $this->collectionCache;
        }
        
        $this->collectionCache = $this->configurationService->getContext('collection') ?? null;
        
        return $this->collectionCache;
    }

    /**
     * Get the current endpoint name
     * 
     * @return string
     */
    public function getEndpoint(): ?string
    {
        if ($this->endpointCache !== null) {
            return $this->endpointCache;
        }
        
        $this->endpointCache = $this->configurationService->getContext('endpoint') ?? null;
        
        return $this->endpointCache;
    }

    /**
     * Get the application environment
     * 
     * @return string
     */
    public function getEnvironment(): ?string
    {
        if ($this->environmentCache !== null) {
            return $this->environmentCache;
        }
        
        $this->environmentCache = $this->kernel->getEnvironment() ?? null;
        
        return $this->environmentCache;
    }

    /**
     * Check if the application is in debug mode
     * 
     * @return bool
     */
    public function isDebug(): bool
    {
        if ($this->debugCache !== null) {
            return $this->debugCache;
        }
        
        $this->debugCache = $this->kernel->isDebug() ?? false;
        
        return $this->debugCache;
    }

    /**
     * Get the bundle directory path
     * 
     * @return string
     */
    public function getBundleDir(): string
    {
        if ($this->bundleDirCache !== null) {
            return $this->bundleDirCache;
        }

        $bundleClass = ApiBundle::class;
        $bundleName  = (new \ReflectionClass($bundleClass))->getShortName();

        $this->bundleDirCache = $this->kernel->getBundle($bundleName)->getPath();

        return $this->bundleDirCache;
    }

    /**
     * Get the project directory path
     * 
     * @return string
     */
    public function getProjectDir(): string
    {
        if ($this->projectDirCache !== null) {
            return $this->projectDirCache;
        }

        $this->projectDirCache = $this->kernel->getProjectDir();

        return $this->projectDirCache;
    }
}