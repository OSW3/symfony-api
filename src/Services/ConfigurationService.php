<?php 
namespace OSW3\Api\Services;

use OSW3\Api\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Bundle Configuration
 */
class ConfigurationService 
{
    private readonly array $configuration;

    public function __construct(
        #[Autowire(service: 'service_container')] private ContainerInterface $container,
    ){
        $this->configuration = $container->getParameter(Configuration::NAME);
    }


    // Bundle Config
    // --

    /**
     * Return an array of all providers definition
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
     * @param string $name
     * @return array
     */
    public function getProvider(string $name): array
    {
        $providers = $this->getProviders();
        $provider  = $providers[$name];

        return $provider;
    }


    // Provider Config
    // --

    // Provider route config

    /**
     * Get the version segment of the path
     *
     * @param string $providerName
     * @return string|null
     */
    public function getPathSegmentVersion(string $providerName): ?string 
    {
        $provider = $this->getProvider($providerName);
        $segment  = $provider['router']['prefix'];

        return $segment;
    }

    /**
     * Get the Singular collection name segment of the path
     *
     * @param string $provider
     * @param string $collection
     * @return string
     */
    public function getPathSegmentSingular(string $provider, string $collection): string 
    {
        $collection = $this->getCollection($provider, $collection);
        $segment    = $collection['paths']['singular'];

        return $segment;
    }
    
    /**
     * Get the Plural collection name segment of the path
     *
     * @param string $provider
     * @param string $collection
     * @return string
     */
    public function getPathSegmentPlural(string $provider, string $collection): string 
    {
        $collection = $this->getCollection($provider, $collection);
        $segment    = $collection['paths']['plural'];

        return $segment;
    }

    /**
     * Get allowed request methods
     *
     * @param string $provider
     * @param string $collection
     * @return array
     */
    public function getAllowedRequestMethods(string $provider, string $collection): array 
    {
        $config = $this->getCollection($provider, $collection);
        $privileges = $config['privileges'];

        $methods = [];

        foreach ($privileges as $privilege)
        {
            $methods = array_merge($methods, $privilege['methods']);
        }

        return array_unique($methods);
    }


    // Provider search

    public function isSearchEnabled(string $providerName): bool
    {
        $provider  = $this->getProvider($providerName);
        $isEnabled = $provider['search']['enabled'];

        return $isEnabled;
    }

    public function getParma(string $providerName): ?int 
    {
        $provider = $this->getProvider($providerName);
        $param    = $provider['search']['params'];

        return $param;
    }


    // Provider pagination

    /**
     * Define if pagination si enabled or not
     *
     * @param string $providerName
     * @return boolean
     */
    public function isPaginationEnabled(string $providerName): bool
    {
        $provider  = $this->getProvider($providerName);
        $isEnabled = $provider['pagination']['enabled'];

        return $isEnabled;
    }

    /**
     * Get items per page
     *
     * @param string $providerName
     * @return int|null
     */
    public function getItemsPerPage(string $providerName): ?int 
    {
        $provider = $this->getProvider($providerName);
        $perPage  = $provider['pagination']['per_page'];

        return $perPage;
    }


    // Provider links

    /**
     * Define if link support is enabled
     *
     * @param string $providerName
     * @return boolean
     */
    public function getLinkSupport(string $providerName): bool 
    {
        $provider = $this->getProvider($providerName);
        $support  = $provider['url_generator']['support'];

        return $support;
    }

    /**
     * Define if generate absolute url or not
     *
     * @param string $providerName
     * @return boolean
     */
    public function isAbsoluteLink(string $providerName): bool 
    {
        $provider   = $this->getProvider($providerName);
        $isAbsolute = $provider['url_generator']['absolute'];

        return $isAbsolute;
    }


    // Provider Collections

    /**
     * Get all collections of a spcified provider
     *
     * @param string $provider
     * @return array
     */
    public function getCollections(string $providerName): array
    {
        $provider    = $this->getProvider($providerName);
        $collections = $provider['collections'];

        return $collections;
    }

    /**
     * Return a spcific collection from a specific provider
     *
     * @param string $providerName
     * @param string $collectionName
     * @return array
     */
    public function getCollection(string $providerName, string $collectionName): array 
    {
        $collections = $this->getCollections($providerName);
        $collection  = $collections[$collectionName];

        return $collection;
    }


    public function getClass(string $providerName, string $collectionName): string
    {
        $collection = $this->getCollection($providerName, $collectionName);
        $class      = $collection['entity_manager']['class'] ?? null;
        
        return $class;
    }
}