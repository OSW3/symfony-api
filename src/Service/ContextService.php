<?php
namespace OSW3\Api\Service;

use OSW3\Api\ApiBundle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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

    private array $context = [];
    private readonly ?Request $request;
    
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly RequestStack $requestStack,
    ){
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * Get the current context part or full context array
     * 
     * @param string|null $part 'provider'|'collection'|'endpoint'
     * @return array|string|null
     */
    private function get(?string $part = null): array|string|null
    {
        if (empty($this->context))
        {
            if (! $this->request || ! $this->request->attributes->get('_route')) {
                return null;
            }

            $context = $this->request->attributes->get('_context', []);

            $this->context = [
                'provider'   => $context['provider']   ?? null,
                'segment'    => $context['segment']    ?? null,
                'collection' => $context['collection'] ?? null,
                'endpoint'   => $context['endpoint']   ?? null,
            ];
        }

        return $part ? $this->context[$part] ?? [] : $this->context;
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
        
        $this->providerCache = $this->get('provider') ?? null;
        
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
        
        $this->segmentCache = $this->get('segment') ?? null;
        
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
        
        $this->collectionCache = $this->get('collection') ?? null;
        
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
        
        $this->endpointCache = $this->get('endpoint') ?? null;
        
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