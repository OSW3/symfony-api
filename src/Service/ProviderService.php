<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ContextService;
use OSW3\Api\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ProviderService 
{
    private readonly array $configuration;

    public function __construct(
        #[Autowire(service: 'service_container')] 
        private readonly ContainerInterface $container,
    ){
        $this->configuration = $container->getParameter(Configuration::NAME);
    }

    /**
     * Get all configured providers
     * 
     * @return array
     */
    public function all(): array
    {
        return $this->configuration['providers'];
    }
    
    /**
     * Get all configured provider names
     * 
     * @return array
     */
    public function names(): array
    {
        return array_keys($this->all());
    }

    /**
     * Count all configured providers
     * 
     * @return int
     */
    public function count(): int
    {
        return count($this->all());
    }

    /**
     * Get a configured provider by name
     * 
     * @param string $provider
     * @return array|null
     */
    public function get(?string $provider): ?array
    {
        return $this->all()[$provider] ?? null;
    }

    /**
     * Check if a provider exists
     * 
     * @param string $provider
     * @return bool
     */
    public function exists(?string $provider): bool
    {
        return array_key_exists($provider, $this->all());
    }

    /**
     * Check if a provider is enabled
     * 
     * @param string $provider
     * @return bool
     */
    public function isEnabled(?string $provider): bool
    {
        if (! $this->exists($provider)) {
            return false;
        }

        return $this->get($provider)['enabled'] ?? false;
    }

    /**
     * Check if a provider has a given segment configured
     * 
     * @param string $provider
     * @param string $segment
     * @return bool
     */
    public function hasSegment(?string $provider, ?string $segment): bool
    {
        if (!$this->exists($provider)) {
            return false;
        }

        if (!in_array($segment, [
            ContextService::SEGMENT_COLLECTION, 
            ContextService::SEGMENT_AUTHENTICATION
        ])) {
            return false;
        }

        return array_key_exists($segment, $this->get($provider));
    }
}