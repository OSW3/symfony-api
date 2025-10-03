<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\Routing\RouteCollection;

class RouteService 
{
    public function __construct(
        private ConfigurationService $configuration,
    ){}

    public function addToCollection(): static 
    {
        $collection = new RouteCollection;
        $providers = $this->configuration->getProviders();

        foreach ($providers as $provider) 
        {
        // dump($provider);

        }

        // dump($providers);
        // dump($collection);
        // dump('Add routes to collection');
        
        return $this;
    }
}