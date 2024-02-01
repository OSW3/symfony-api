<?php 
namespace OSW3\Api\Services;

use Symfony\Component\Routing\Route;
use OSW3\Api\Services\ConfigurationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use OSW3\Api\DependencyInjection\Configuration;

class RouteService 
{
    public function __construct(
        private ConfigurationService $configuration,
        private RouterInterface $routerInterface,
    ){}

    public function addCollection(): static
    {
        $routeCollection = new RouteCollection();
        $providers = array_keys($this->configuration->getProviders());
        $routes = [];

        foreach ($providers as $provider)
        {
            // Get all collections of a provider
            $collections = $this->configuration->getCollections($provider);

            foreach (array_keys($collections) as $collection)
            {
                foreach ($this->configuration->getAllowedRequestMethods($provider, $collection) as $method)
                {
                    $actions = match($method)
                    {
                        Request::METHOD_GET    => ['index','read'],
                        Request::METHOD_POST   => ['create'],
                        Request::METHOD_PUT    => ['update', 'create'],
                        Request::METHOD_PATCH  => ['update'],
                        Request::METHOD_DELETE => ['delete'],
                    };

                    foreach ($actions as $action)
                    {
                        $name = $this->generateName($provider, $collection, $action);

                        if (!isset($routes[$name]))
                        {
                            $routes[$name] = [
                                'path'         => $this->getPath($provider, $collection, $action),
                                'defaults'     => $this->getDefaults(),
                                'requirements' => $this->getRequirements($action),
                                'options'      => $this->getOptions(),
                                'host'         => $this->getHost(),
                                'schemes'      => $this->getSchemes(),
                                'methods'      => [],
                                'conditions'   => $this->getConditions(),
                            ];
                        }

                        array_push($routes[$name]['methods'], $method);
                    } 
                }
            }
        }

        foreach ($routes as $name => $route)
        {
            $routeCollection->add($name, new Route( 
                $route['path'], 
                $route['defaults'], 
                $route['requirements'], 
                $route['options'], 
                $route['host'], 
                $route['schemes'], 
                $route['methods'], 
                $route['conditions'] 
            ));
        }

        $this->routerInterface->getRouteCollection()->addCollection( $routeCollection );

        return $this;
    }

    public function generateName(string $provider, string $collection, string $action): string
    {
        $configuration = $this->configuration->getProvider($provider);

        $name = $configuration['router']['name'];
        $name = str_replace("{provider}", $provider, $name);
        $name = str_replace("{collection}", $collection, $name);
        $name = str_replace("{action}", $action, $name);

        return $name;
    }


    public function pathToRegex($pattern)
    {
        $sections = explode("/", $pattern);
        unset($sections[0]);
    
        foreach ($sections as $key => $term)
        {
            $isParam = preg_match("/^{(.+)}$/", $term, $param);
            $regex = $isParam && isset($param[1]) && isset($route['requirement'][$param[1]]) ? $route['requirement'][$param[1]] : ".+";
            $param = $param[1] ?? null;

            $sections[$key] = [
                'term'    => $term,
                'isParam' => $isParam,
                'regex'   => $regex,
                'param'   => $param,
            ];
        }

        $re = '';
        foreach ($sections as $key => $section)
        {
            $re.= $section['isParam'] 
                ? '/(?P<'.$section['param'].'>'.$section['regex'].')'
                // ? '/('.$section['regex'].')'
                : '/'.$section['term']
            ;
        }

        // return $re;
        return sprintf('#^%s$#', $re);
    }



    private function getPath(string $provider, string $collection, string $action): string
    {
        $segment  = $this->configuration->getPathSegmentVersion($provider);
        $singular = $this->configuration->getPathSegmentSingular($provider, $collection);
        $plural   = $this->configuration->getPathSegmentPlural($provider, $collection);
        
        $path = Configuration::BASE_PATH;
        $path.= !empty($segment) ? "/{$segment}" : "";
        $path.= $action === 'index' ? "/{$plural}" : "/{$singular}";
        $path.= !in_array($action,['index','create']) ? "/{id}" : "";
        $path = str_replace("//", "/", $path);

        return $path;
    }

    private function getDefaults(): array 
    {
        return [];
    }

    private function getRequirements(string $action): array 
    {
        $requirements = [];

        if (!in_array($action,['index','create']))
        {
            $requirements['id'] = "\d+|[\w-]+";
        }
        
        return $requirements;
    }

    private function getOptions(): array 
    {
        return [];
    }

    private function getHost(): string 
    {
        return '';
    }

    private function getSchemes(): array 
    {
        return [];
    }

    private function getMethod(string $method): array 
    {
        return [$method];
    }

    private function getConditions(): string 
    {
        return '';
    }
}