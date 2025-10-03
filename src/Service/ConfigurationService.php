<?php 
namespace OSW3\Api\Service;

use OSW3\Api\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigurationService
{
    private readonly array $configuration;

    public function __construct(
        #[Autowire(service: 'service_container')] private ContainerInterface $container,
    ){
        $this->configuration = $container->getParameter(Configuration::NAME);
    }

    /**
     * Get providers definition
     * Represent the api.yaml
     * 
     * @return array
     */
    public function getProviders(): array 
    {
        return $this->configuration;
    }

    /**
     * Return the definition array of a specified provider
     *
     * @param string $provider, The name ou the provider (entity namespace)
     * @return array
     */
    public function getProvider(string $provider): array
    {
        $providers = $this->getProviders();

        return $providers[$provider] ?? [];
    }
}