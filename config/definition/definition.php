<?php

use OSW3\Api\Generator\ApiVersionGenerator;
use OSW3\Api\Generator\CollectionNameGenerator;
use OSW3\Api\Validator\EntityValidator;

return static function($definition)
{
    $definition->rootNode()
        ->info('Configure all API providers and their behaviour.')

        // ──────────────────────────────
        // Version Providers (v1, v2…)
        // ──────────────────────────────
        ->useAttributeAsKey('version_provider')
        ->arrayPrototype()
        ->info('Each key is an API provider. Typically used to group routes, versions and settings.')
        ->children()


            // ──────────────────────────────
            // Versioning
            // ──────────────────────────────
            ->scalarNode('version')
                ->info('Specify the version of the API. If null, it will be automatically generated (v1, v2, …).')
                ->defaultNull()
            ->end()


            // ──────────────────────────────
            // Global route settings
            // ──────────────────────────────
			->arrayNode('routes')
            ->info('Default route naming and URL prefix for this API provider.')
            ->addDefaultsIfNotSet()->children()

                ->scalarNode('name')
                    ->info('Pattern for route names. Available placeholders: {version}, {collection}, {action}.')
                    ->defaultValue('api:{version}:{collection}:{action}')
                ->end()

                ->scalarNode('prefix')
                    ->info('Default URL prefix for all routes in this API version.')
                    ->defaultValue('/api/{version}')
                ->end()
                
            ->end()->end()


            // ──────────────────────────────
            // Search configuration
            // ──────────────────────────────
            ->booleanNode('search')
                ->info('Enable or disable search globally for all collections.')
                ->defaultTrue()
            ->end()
            

            // ──────────────────────────────
            // Pagination defaults
            // ──────────────────────────────
			->arrayNode('pagination')
            ->info('Default pagination behaviour for all collections.')
            ->addDefaultsIfNotSet()->children()

				->booleanNode('enable')
                    ->info('Enable or disable pagination globally.')
                    ->defaultTrue()
                ->end()

				->integerNode('per_page')
                    ->info('Default number of items per page.')
                    ->defaultValue(10)
                    ->min(1)
                ->end()

			->end()->end()


            // ──────────────────────────────
            // URL response settings
            // ──────────────────────────────
			->arrayNode('url')
            ->info('Control if URLs are included in responses and how they are generated.')
            ->addDefaultsIfNotSet()->children()

				->booleanNode('support')
                    ->info('Whether to include URL elements in API responses.')
                    ->defaultTrue()
                ->end()

				->booleanNode('absolute')
                    ->info('Generate absolute URLs if true, relative otherwise')
                    ->defaultTrue()
                ->end()

			->end()->end()


            // ──────────────────────────────
            // Collections (Doctrine Entities)
            // ──────────────────────────────
			->arrayNode('collections')
            ->info('List of Doctrine entity classes to expose as REST collections.')
            ->useAttributeAsKey('entity')  
                ->arrayPrototype()
                ->ignoreExtraKeys(false)
                    ->children()

                        // Collection name
                        ->scalarNode('name')
                            ->info('Collection name in URLs and route names. Auto-generated from entity if null (e.g. App\\Entity\\Book → books).')
                            ->defaultNull()
                        ->end()

                        // Per-collection route overrides
                        ->arrayNode('route')
                            ->info('Override default route name or URL prefix for this specific collection.')
                            ->addDefaultsIfNotSet()->children()

                                ->scalarNode('name')
                                    ->info('Custom route name pattern. Falls back to global `routes.name` if null.')
                                    ->defaultNull()
                                ->end()

                                ->scalarNode('prefix')
                                    ->info('Custom URL prefix. Falls back to global `routes.prefix` if null.')
                                    ->defaultNull()
                                ->end()
                            
                            ->end()
                        ->end()

                        // Per-collection search override
                        ->booleanNode('search')
                            ->info('Override global search setting for this specific collection.')
                            ->defaultNull()
                        ->end()

                        // Per-collection pagination override
                        ->integerNode('pagination')
                            ->info('Override pagination items per page for this collection.')
                            ->defaultNull()
                            ->min(1)
                        ->end()

                        // REST endpoints
                        ->arrayNode('endpoints')
                        ->info('Configure the endpoints available for this collection. Default: index, create, read, update, delete.')
                        ->useAttributeAsKey('endpoint')  
                            ->arrayPrototype()
                            ->ignoreExtraKeys(false)
                                ->children()

                                    ->scalarNode('name')
                                        ->info('route name. Defaults to null.')
                                        ->defaultNull()
                                    ->end()

                                    ->arrayNode('granted')
                                        ->info('Access control for this action (security roles, PUBLIC_ACCESS, etc.).')
                                        ->scalarPrototype()->end()
                                    ->end()

                                    ->arrayNode('methods')
                                        ->info('Allowed HTTP methods for this action.')
                                        ->scalarPrototype()->end()           
                                    ->end()

                                    ->scalarNode('controller')
                                        ->info('Optional Symfony controller (FQCN::method). Defaults to null.')
                                        ->defaultNull()
                                    ->end()

                                ->end()
                            ->end()
                        ->end()

                    ->end() // of collections  arrayPrototype children
                ->end() // of collections arrayPrototype

                // Validation: entity existence
                ->validate()
                    ->ifTrue(fn($v) => EntityValidator::validateClassesExist($v))
                    ->thenInvalid('One or more entities defined in "api" do not exist. Check namespaces and spelling.')
                ->end()

                // Automatic collection name generation
                ->validate()
                    ->always(fn($collections) => CollectionNameGenerator::generate($collections))
                ->end()

            ->end() // of collections

        ->end() // of version_provider
    ->end() // of rootNode


    // ──────────────────────────────
    // Final post-processing
    // ──────────────────────────────
    ->validate()
        ->always(function($providers) {
            
            // 1. Generate missing versions
            $providers = ApiVersionGenerator::generate($providers);

            foreach ($providers as $n => &$provider) 
            {
                foreach ($provider['collections'] as $entityName => &$collection)
                {

                    // 2. Fallback: route name & prefix
                    if ($collection['route']['name'] == null) 
                    {
                        $collection['route']['name'] = $provider['routes']['name'];
                    }
                    if ($collection['route']['prefix'] == null) 
                    {
                        $collection['route']['prefix'] = $provider['routes']['prefix'];
                    }

                    $collection['route']['prefix'] = preg_replace("/{version}/", $provider['version'], $collection['route']['prefix']);
                    // $collection['route']['name'] = preg_replace("/{collection}/", $collection['name'], $collection['route']['name']);

                    // 3. Fallback: search & pagination
                    if (!is_bool($collection['search'])) 
                    {
                        $collection['search'] = $provider['search'];
                    }
                    if ($collection['pagination'] == null) 
                    {
                        $collection['pagination'] = $provider['pagination']['per_page'];
                    }


                    // 4. Inject default REST actions if missing
                    $collection['endpoints'] = array_merge([
                        'index' => [
                            'granted' => ['PUBLIC_ACCESS'],
                            'methods' => ['GET'],
                        ],
                        // 'create' => [
                        //     'granted' => ['PUBLIC_ACCESS'],
                        //     'methods' => ['HEAD', 'POST'],
                        // ],
                        // 'read' => [
                        //     'granted' => ['PUBLIC_ACCESS'],
                        //     'methods' => ['GET'],
                        //     'options' => ['id'],
                        //     'requirements' => [
                        //         'id' => "\d+|[\w-]+"
                        //     ],
                        // ],
                        // 'update' => [
                        //     'granted' => ['PUBLIC_ACCESS'],
                        //     'methods' => ['HEAD', 'PUT'],
                        // ],
                        // 'delete' => [
                        //     'granted' => ['PUBLIC_ACCESS'],
                        //     'methods' => ['HEAD', 'DELETE'],
                        // ],
                    ], $collection['endpoints']);




                    // 5. Normalize missing action fields
                    foreach ($collection['endpoints'] as $endpointName => &$endpoint) 
                    {
                        // Endpoint route name
                        if (!isset($endpoint['name'])) 
                        {
                            $endpoint['name'] = $collection['route']['name'];
                        }
                        
                        // Endpoint route name
                        if (!isset($endpoint['methods'])) 
                        {
                            $endpoint['methods'] = [];
                        }
                        
                        // Endpoint route requirements
                        if (!isset($endpoint['requirements'])) 
                        {
                            $endpoint['requirements'] = [];
                        }
                        
                        // Endpoint route defaults params values
                        // if (!isset($endpoint['defaults'])) 
                        // {
                        //     $endpoint['defaults'] = [];
                        // }
                        
                        // Endpoint route options
                        if (!isset($endpoint['options'])) 
                        {
                            $endpoint['options'] = [];
                        }
                        
                        // Endpoint route conditions
                        if (!isset($endpoint['conditions'])) 
                        {
                            $endpoint['conditions'] = '';
                        }
                        
                        // Endpoint route host
                        if (!isset($endpoint['host'])) 
                        {
                            $endpoint['host'] = '';
                        }
                        
                        // Endpoint route schemes
                        if (!isset($endpoint['schemes'])) 
                        {
                            $endpoint['schemes'] = [];
                        }

                        if (empty($endpoint['granted'])) 
                        {
                            $endpoint['granted'] = ['PUBLIC_ACCESS'];
                        }
                        if (!isset($endpoint['controller'])) 
                        {
                            $endpoint['controller'] = null;
                        }


                        $className = (new \ReflectionClass($entityName))->getShortName();
                        $className = strtolower($className);
                        $endpoint['name'] = preg_replace("/{version}/", $provider['version'], $endpoint['name']);
                        $endpoint['name'] = preg_replace("/{action}/", $endpointName, $endpoint['name']);
                        $endpoint['name'] = preg_replace("/{collection}/", $className, $endpoint['name']);
                    }

                }
            }

            return $providers;
        })
    ->end(); // of Version generator
 };