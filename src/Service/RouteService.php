<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class RouteService 
{
    private readonly ?Request $request;
    
    public function __construct(
        private readonly ConfigurationService $configuration,
        private readonly RequestStack $requestStack,
        private readonly RouterInterface $routerInterface,
        private readonly VersionService $versionService,
    ){
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getExposedRoutes(): array
    {
        $providers = $this->configuration->getProviders();
        $routes = [];


        // Read APIs definitions
        // my_custom_api_v1, my_custom_api_v2, ...
        foreach ($providers as $providerName => $provider) {

            // Add registration route if enabled
            // -- 

            if ($this->configuration->isRegistrationEnabled($providerName)) {
                $this->addRegisterRoute($routes, $providerName);
            }


            // Add authentication route if enabled
            // -- 

            if ($this->configuration->isLoginEnabled($providerName)) {
                $this->addLoginRoute($routes, $providerName);
            }


            // Add collection routes if enabled
            // --

            foreach ($provider['collections'] ?? [] as $entityClass => $entityOptions) {
                foreach ($entityOptions['endpoints'] ?? [] as $endpointName => $endpointOption) 
                {
                    if (!$endpointOption['enabled']) {
                        continue;
                    }

                    // Route Name
                    $name = $endpointOption['route']['name'];

                    // Route path
                    // $prefix     = preg_replace("#/$#", "", $entityOptions['route']['prefix']);
                    // $collection = $entityOptions['name'];
                    // $path       = "{$prefix}/{$collection}";
                    $path       = $endpointOption['route']['path'];

                    // dd( $prefix, $entityOptions['route']['prefix'], $collection, $endpointOption['route']['path'] );
                    foreach ($endpointOption['route']['options'] ?? [] as $opt) $path .= "/{{$opt}}";


                    // Route defaults
                    $defaults = [];
                    $defaults['_controller'] = $endpointOption['route']['controller'] ?? null;
                    $defaults['_api_endpoint'] = $endpointName;
        
                    // Route requirements
                    $requirements = $endpointOption['route']['requirements'] ?? [];
        
                    // Route options
                    $options = $endpointOption['route']['options'] ?? [];
                    $options['context'] = [
                        'provider'   => $providerName,
                        'collection' => $entityClass,
                        'endpoint'   => $endpointName,
                    ];
        
                    // Route host
                    $host = $endpointOption['route']['host'] ?? null;
                    // $host = 'osw3.net';
        
                    // Route schemes
                    $schemes = $endpointOption['route']['schemes'] ?? [];
                    // $schemes = ['http', 'https'];
                    // $schemes = [];
        
                    // Route methods
                    $methods = $endpointOption['route']['methods'] ?? [];
        
                    // Route conditions
                    $condition = $endpointOption['route']['condition'] ?? '';
        

                    $this->addRouteToCollection($routes, [
                        'name'         => $name,
                        'path'         => $path,
                        'defaults'     => $defaults,
                        'requirements' => $requirements,
                        'options'      => $options,
                        'host'         => $host,
                        'schemes'      => $schemes,
                        'methods'      => $methods,
                        'condition'    => $condition,
                    ]);
                }
            }
        }

        return $routes;
    }

    public function getRouteNameByProvider(string $provider, string $action): string|null 
    {
        $pattern    = $this->configuration->getRouteNamePattern($provider);
        $version    = $this->versionService->getLabel($provider);
        $collection = $this->configuration->getSecurityCollectionName($provider);

        $route      = $pattern;
        $route      = preg_replace("/{version}/", $version, $route);
        $route      = preg_replace("/{action}/", $action, $route);
        $route      = preg_replace("/{collection}/", $collection, $route);

        return $route;
    }

    public function getRoutePathByProvider(string $provider, string $action): string|null 
    {
        $prefix  = $this->configuration->getRoutePrefix($provider);
        $prefix  = preg_replace("#/$#", "", $prefix);
        $version = $this->versionService->getLabel($provider);

        return "{$prefix}/{$version}/{$action}";
    }

    public function getCurrentRoute(): array|null 
    {
        $routeName = $this->request?->attributes->get('_route');

        if (!$routeName) {
            return null;
        }

        $exposedRoutes = $this->getExposedRoutes();

        return $exposedRoutes[$routeName] ?? null;
    }

    public function getContext(): array
    {
        $route = $this->getCurrentRoute();
        $options = $route ? $route['options'] : [];

        return $options['context'] ?? [];
    }

    private function addRegisterRoute(array &$routes, string $provider): void 
    {
        $context = [
            'provider' => $provider,
            'endpoint' => "register",
            'collection' => $this->configuration->getSecurityCollectionName($provider),
        ];

        $name                      = $this->getRouteNameByProvider($provider, $context['endpoint']);
        $path                      = $this->configuration->getRegistrationPath($provider) ?? $this->getRoutePathByProvider($provider, $context['endpoint']);
        $defaults                  = [];
        $defaults['_controller']   = $this->configuration->getRegistrationController($provider);
        $defaults['_api_endpoint'] = $context['endpoint'];
        // $requirements              = [];
        $options                   = ['context' => $context];
        // $host                      = null;
        // $schemes                   = [];
        $methods                   = [$this->configuration->getRegistrationMethod($provider) ?: 'POST'];
        // $condition                 = '';

        $this->addRouteToCollection($routes, [
            'name'         => $name,
            'path'         => $path,
            'defaults'     => $defaults,
            // 'requirements' => $requirements,
            'options'      => $options,
            // 'host'         => $host,
            // 'schemes'      => $schemes,
            'methods'      => $methods,
            // 'condition'    => $condition,
        ]);
    }

    private function addLoginRoute(array &$routes, string $provider): void 
    {
        $context = [
            'provider' => $provider,
            'endpoint' => "login",
            'collection' => $this->configuration->getSecurityCollectionName($provider),
        ];

        $name                      = $this->getRouteNameByProvider($provider, $context['endpoint']);
        $path                      = $this->configuration->getLoginPath($provider) ?? $this->getRoutePathByProvider($provider, $context['endpoint']);
        $defaults                  = [];
        $defaults['_controller']   = $this->configuration->getLoginController($provider);
        $defaults['_api_endpoint'] = $context['endpoint'];
        // $requirements              = [];
        $options                   = ['context' => $context];
        // $host                      = null;
        // $schemes                   = [];
        $methods                   = [$this->configuration->getLoginMethod($provider) ?: 'POST'];
        // $condition                 = '';

        $this->addRouteToCollection($routes, [
            'name'         => $name,
            'path'         => $path,
            'defaults'     => $defaults,
            // 'requirements' => $requirements,
            'options'      => $options,
            // 'host'         => $host,
            // 'schemes'      => $schemes,
            'methods'      => $methods,
            // 'condition'    => $condition,
        ]);
    }

    private function addRouteToCollection(array &$route, array $options = []): void 
    {
        if (!isset($route[$options['name']])) {
            $route[$options['name']] = [
                'path'         => $options['path'] ?? '',
                'defaults'     => $options['defaults'] ?? [],
                'requirements' => $options['requirements'] ?? [],
                'options'      => $options['options'] ?? [],
                'host'         => $options['host'] ?? null,
                'schemes'      => $options['schemes'] ?? [],
                'methods'      => $options['methods'] ?? [],
                'condition'    => $options['condition'] ?? '',
            ];
        }
    }

    /**
     * Check if a given route is registered in the API configuration
     *
     * @param string $route
     * @return bool
     */
    public function isRegisteredRoute(string $route): bool 
    {
        // $providers = $this->configuration->getProviders();
        // $routes = [];

        // dump($this->getExposedRoutes());


        foreach ($this->getExposedRoutes() as $routeName => $routeOptions) {
            // $routes[] = $routeName;

            if ($routeName === $route) {
                return true;
            }
        }

        // foreach ($providers as $provider) {

        //     $routes[] = $provider['security']['routes']['collection'] ?? '';

        //     foreach ($provider['collections'] ?? [] as $collection) {
        //         foreach ($collection['endpoints'] ?? [] as $endpoint) {

        //             $routes[] = $endpoint['route']['name'] ;
                    
        //         }
        //     }
        // }


        // dd($routes);


        // foreach ($providers as $provider) {
        //     foreach ($provider['collections'] ?? [] as $collection) {
        //         foreach ($collection['endpoints'] ?? [] as $endpoint) {

        //             dump($endpoint['route']['name'] );

        //             if (($endpoint['route']['name'] ?? null) === $route) {
        //                 return true;
        //             }
        //         }
        //     }
        // }

        return false;
    }

    /**
     * Check if the current request method is supported by the given route
     *
     * @param string $route
     * @return bool
     */
    public function isMethodSupported(string $route): bool 
    {
        // $providers = $this->configuration->getProviders();
        $method    = $this->request->getMethod();


        foreach ($this->getExposedRoutes() as $routeName => $routeOptions) {
            if ($routeName === $route && in_array($method, $routeOptions['methods'] ?? [], true)) {
                return true;
            }
        }




        // foreach ($providers as $provider) {
        //     foreach ($provider['collections'] ?? [] as $collection) {
        //         foreach ($collection['endpoints'] ?? [] as $endpoint) {
        //             if (($endpoint['route']['name'] ?? null) === $route) {
        //                 $allowed = $endpoint['route']['methods'] ?? [];
        //                 return in_array($method, $allowed, true);
        //             }
        //         }
        //     }
        // }

        return false;
    }
}