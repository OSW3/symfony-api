<?php 
namespace OSW3\Api\Services;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The (current) request configuration
 */
class RequestService 
{
    private string $provider;
    private array $collection;

    public function __construct(
        private ConfigurationService $configuration,
        private RequestStack $requestStack,
        private RouteService $routeService,
        private RouterInterface $routerInterface,
        private Security $security,
    ){}


    // Request Support
    // --

    /**
     * Is current request is an API request
     *
     * @return boolean
     */
    public function supports(): bool
    {
        $support = $this->requestStack->getCurrentRequest()->get('_route') === 'api'
                && $this->getRoute() !== null;

        if ($support) $this->findCurrentCollection();

        return $support;
    }


    // Route 
    // --

    /**
     * Get current route name
     *
     * @return string|null
     */
    public function getRoute(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        $path    = $request->getPathInfo();
        $method  = $request->getMethod();
        $routes  = $this->routerInterface->getRouteCollection()->all();

        foreach ($routes as $name => $route) if (!str_starts_with($name, '_') && $name != 'api') 
        {
            $pattern = $route->getPath();
            $methods = $route->getMethods();
            $regex   = $this->routeService->pathToRegex($pattern);

            if (preg_match($regex, $path) && in_array($method, $methods))
            {
                return $name;
            }
        }

        return null;
    }


    // URI Parameters
    // --

    // Collection ID

    /**
     * Get the request ID parameter
     *
     * @return string|integer|null
     */
    public function getId(): string|int|null
    {
        $id = $this->requestStack->getCurrentRequest()->get('id');
        $id = intval($id) == $id ? intval($id) : $id;
        $id = !empty($id) ? $id : null;

        return $id;
    }

    // Collection sorter

    /**
     * Get the request sorter parameter
     *
     * @return array
     */
    public function getSorter(): array
    {
        $sorter  = [];
        $properties = [];
        $request = $this->requestStack->getCurrentRequest();

        if ($request->get('sorter'))
        {
            $properties = explode(",", $request->get('sorter'));

            foreach ($properties as $key => $definition)
            {
                $definition = explode(":", $definition);
                $properties[$definition[0]] = ['order' => strtoupper($definition[1])];
                unset($properties[$key]);
            }
        }

        foreach ($properties as $name => $data)
        {
            $sorter[$name] = $data['order'];
        }

        return $sorter;
    }

    // Pagination

    /**
     * Get the current page number
     * used for collection list or search results
     *
     * @return integer
     */
    public function getPage(): int 
    {
        return $this->requestStack->getCurrentRequest()->get('page') ?? 1;
    }

    /**
     * Get item per page
     *
     * @return integer|null
     */
    public function getItemsPerPage(): ?int
    {
        $provider = $this->getProvider();
        $default  = $this->configuration->getItemsPerPage($provider);
        $custom   = $this->requestStack->getCurrentRequest()->get('perPage');

        return $custom ?? $default;
    }


    // Current Configuration
    // --

    /**
     * Get the current collection config
     *
     * @return void
     */
    private function findCurrentCollection(): void
    {
        if ($this->provider ?? null)
        {
            return;
        }

        $route     = $this->getRoute();
        $providers = array_keys($this->configuration->getProviders());

        foreach ($providers as $provider)
        {
            // Get all collections of a provider
            $collections = $this->configuration->getCollections($provider);

            foreach (array_keys($collections) as $collection)
            {
                foreach (['index','read','create','update','update','delete'] as $action) 
                {
                    if ($this->routeService->generateName($provider, $collection, $action) === $route)
                    {
                        $this->provider   = $provider;
                        $this->collection = $this->configuration->getCollection($provider, $collection);
                        return;
                    }
                }
            }
        }
    }

    /**
     * Get the current provider name
     *
     * @return string|null
     */
    public function getProvider(): ?string
    {
        return $this->provider ?? null;
    }

    /**
     * Get the current collection data
     *
     * @return array
     */
    public function getCollection(): array
    {
        return $this->collection ?? [];
    }
    
    /**
     * Get the current collection class
     *
     * @return string
     */
    public function getClass(): string 
    {
        $collection = $this->getCollection();
        $class      = $collection['entity_manager']['class'] ?? null;
        
        return $class;
    }

    /**
     * Get the repository method
     *
     * @param string $name
     * @return string|null
     */
    public function getMethod(string $name): ?string 
    {
        $collection = $this->getCollection();
        $methods    = $collection['entity_manager']['methods'] ?? [];
        $method     = $methods[$name] ?? null;

        return $method;
    }

    /**
     * Get serializer group keys
     *
     * @return array
     */
    public function getSerializerGroups(): array
    {
        $collection = $this->getCollection();
        $groups     = $collection['entity_manager']['groups'];

        return $groups;
    }

    /**
     * Get privileges of the user on the current request
     *
     * @return array
     */
    public function getPrivileges(): array 
    {
        $collection = $this->getCollection();
        $privileges = $collection['privileges'];

        return $privileges;
    }
}