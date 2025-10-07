<?php

use OSW3\Api\Validator\HooksValidator;
use OSW3\Api\Validator\EntityValidator;
use OSW3\Api\Validator\ControllerValidator;
use OSW3\Api\Validator\TransformerValidator;
use OSW3\Api\Resolver\CollectionNameResolver;
use OSW3\Api\Resolver\ApiVersionNumberResolver;
use OSW3\Api\Resolver\CollectionRouteNameResolver;
use OSW3\Api\Resolver\CollectionRoutePrefixResolver;
use OSW3\Api\Resolver\CollectionSearchStatusResolver;

return static function($definition)
{
    $definition->rootNode()
        ->info('Configure all API providers and their behavior.')

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

			->arrayNode('version')
            ->info('API version configuration')
            ->addDefaultsIfNotSet()->children()

                ->scalarNode('number')
                    ->info('Version number (null = auto-assigned)')
                    ->defaultNull()
                ->end()

                ->scalarNode('prefix')
                    ->info('Version prefix (e.g. "v")')
                    ->defaultValue('v')
                ->end()

                ->enumNode('type')
                    ->info('How the version is exposed: in URL path, HTTP header, query parameter, or subdomain')
                    ->values(['path', 'header', 'param', 'subdomain'])
                    ->defaultValue('path')
                ->end()
                
            ->end()->end()

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
                ->defaultFalse()
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

				->integerNode('max_per_page')
                    ->info('Max number of items per page.')
                    ->defaultValue(100)
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
            // Response Template
            // ──────────────────────────────

            ->scalarNode('template')
                ->info('')
                ->defaultValue('Resources/templates/response.yaml')
            ->end()

			// ->arrayNode('template')
            // ->info('.')
            // ->addDefaultsIfNotSet()->children()

			// 	->booleanNode('support')
            //         ->info('')
            //         ->defaultTrue()
            //     ->end()

			// 	->booleanNode('absolute')
            //         ->info('')
            //         ->defaultTrue()
            //     ->end()

			// ->end()->end()

            // ──────────────────────────────
            // Collections (Doctrine Entities)
            // ──────────────────────────────
			->arrayNode('collections')
            ->info('List of Doctrine entity classes to expose as REST collections.')
            ->useAttributeAsKey('entity')  
                ->arrayPrototype()
                ->ignoreExtraKeys(false)
                    ->children()

                        // ──────────────────────────────
                        // Collection name
                        // ──────────────────────────────
                        ->scalarNode('name')
                            ->info('Collection name in URLs and route names. Auto-generated from entity if null (e.g. App\\Entity\\Book → books).')
                            ->defaultNull()
                        ->end()

                        // ──────────────────────────────
                        // Per-collection route overrides
                        // ──────────────────────────────
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

                        // ──────────────────────────────
                        // Per-collection search override
                        // ──────────────────────────────
                        ->arrayNode('search')
                            ->info('Search configuration for this collection. Allows enabling search and specifying searchable fields.')
                            ->addDefaultsIfNotSet()
                            ->children()

                                ->booleanNode('enabled')
                                    ->info('Enable or disable search for this collection.')
                                    ->defaultNull()
                                ->end()

                                ->arrayNode('fields')
                                    ->info('List of entity fields that are searchable. Only used if search is enabled.')
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()

                            ->end()
                        ->end()

                        // ──────────────────────────────
                        // Per-collection pagination override
                        // ──────────────────────────────
                        ->integerNode('pagination')
                            ->info('Override pagination items per page for this collection.')
                            ->defaultNull()
                            ->min(1)
                        ->end()

                        // ──────────────────────────────
                        // REST endpoints
                        // ──────────────────────────────
                        ->arrayNode('endpoints')
                        ->info('Configure the endpoints available for this collection. Default: index, create, read, update, delete.')
                        ->useAttributeAsKey('endpoint')  
                            ->requiresAtLeastOneElement()
                            ->arrayPrototype()
                            ->ignoreExtraKeys(false)
                                ->children()

                                    // ──────────────────────────────
                                    // Route config
                                    // ──────────────────────────────
                                    ->arrayNode('route')
                                    ->info('Defines the HTTP configuration for the endpoint: route name, path, HTTP methods, controller, constraints, and routing options.')
                                        ->isRequired()
                                        ->children()

                                            ->scalarNode('name')
                                                ->info('Route name. If not defined, it will be generated automatically based on the collection and endpoint name.')
                                                ->defaultNull()
                                            ->end()

                                            // ->scalarNode('path')
                                            //     ->info('Optional custom path for this endpoint.')
                                            //     ->defaultNull()
                                            // ->end()

                                            ->arrayNode('methods')
                                                ->info('Allowed HTTP methods. Must be explicitly defined to avoid accidental exposure.')
                                                ->requiresAtLeastOneElement()
                                                ->isRequired()
                                                ->scalarPrototype()->end()
                                            ->end()

                                            ->scalarNode('controller')
                                                ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                ->defaultNull()
                                                ->validate()
                                                    ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                    ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                ->end()
                                            ->end()

                                            ->arrayNode('requirements')
                                                ->info('Regex constraints for dynamic route parameters. Keys are parameter names, values are regular expressions that must be matched. For example: {id} must be digits, {slug} must be lowercase letters and dashes.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                            ->end()

                                            ->arrayNode('options')
                                                ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                            ->end()

                                            ->scalarNode('condition')
                                                ->info('Optional condition expression for the route.')
                                                ->defaultNull()
                                            ->end()

                                            // TODO: Scheme
                                            // TODO: Host

                                        ->end()
                                    ->end()

                                    // ──────────────────────────────
                                    // Repository config
                                    // ──────────────────────────────
                                    ->arrayNode('repository')
                                    ->info('Specifies how data is retrieved: repository method to call, query criteria, ordering, limits, and loading strategy.')
                                        // ->isRequired()
                                        ->addDefaultsIfNotSet()
                                        ->children()

                                            ->scalarNode('service')
                                                ->info('Optional: the service ID of a custom repository. Defaults to the default Doctrine repository for the entity.')
                                                ->defaultNull()
                                            ->end()

                                            ->scalarNode('method')
                                                ->info(<<<'INFO'
                                                    Repository method to call. This can be either:
                                                    - A standard Doctrine repository method: `find`, `findAll`, `findBy`, `findOneBy`, `count`.
                                                    - A custom public method defined in your repository class.
                                                    The method will be called when the controller is null.
                                                    INFO)
                                                // ->isRequired()
                                                // ->cannotBeEmpty()
                                                ->defaultNull()
                                            ->end()

                                            ->arrayNode('criteria')
                                                ->info('Optional array of criteria to filter results when using `findBy` or `findOneBy`. Keys are field names, values are the values to match.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                            ->end()

                                            ->arrayNode('order_by')
                                                ->info('Optional array defining sorting. Keys are field names, values are `ASC` or `DESC`.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                            ->end()

                                            ->integerNode('limit')
                                                ->info('Optional maximum number of results to return.')
                                                ->defaultNull()
                                            ->end()

                                            ->enumNode('fetch_mode')
                                                ->info('Optional fetch mode for Doctrine relations. "lazy" loads relations on demand, "eager" loads them immediately.')
                                                ->values(['lazy', 'eager'])
                                                ->defaultNull()
                                            ->end()

                                        ->end()
                                    ->end()

                                    // ──────────────────────────────
                                    // Metadata config
                                    // ──────────────────────────────
                                    ->arrayNode('metadata')
                                        ->info('Holds functional metadata for the endpoint: description, deprecation status, caching, rate limiting, and documentation-related details.')
                                        ->addDefaultsIfNotSet()
                                        ->children()

                                            ->scalarNode('description')
                                                ->info('Short human-readable description of the endpoint. Used for documentation (e.g. OpenAPI summary).')
                                                ->defaultNull()
                                            ->end()

                                            ->scalarNode('summary')
                                                ->info('A brief one-line summary for OpenAPI documentation. Useful when "description" is longer.')
                                                ->defaultNull()
                                            ->end()

                                            ->booleanNode('deprecated')
                                                ->info('Marks the endpoint as deprecated. Tools like Swagger will highlight this.')
                                                ->defaultFalse()
                                            ->end()

                                            ->integerNode('cache_ttl')
                                                ->info('Optional cache lifetime in seconds for responses. Can be used for HTTP cache headers or reverse proxy hints.')
                                                ->defaultNull()
                                            ->end()

                                            // ->scalarNode('rate_limit')
                                            //     ->info('Optional rate limit policy for this endpoint, e.g., "100/hour". Purely informative here unless implemented elsewhere.')
                                            //     ->defaultNull()
                                            // ->end()

                                            ->booleanNode('internal_only')
                                                ->info('If true, marks the endpoint as internal (not exposed in public documentation or API discovery)s.')
                                                ->defaultFalse()
                                            ->end()

                                            ->scalarNode('tags')
                                                ->info('Optional tags to group endpoints in documentation tools like Swagger UI. Accepts a comma-separated string or array.')
                                                ->defaultNull()
                                            ->end()

                                            ->arrayNode('examples')
                                                ->info('Optional example request/response objects for documentation and developer experience. Keys are media types, values are example payloads.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            ->scalarNode('operation_id')
                                                ->info('Custom operationId for OpenAPI documentation. If null, it will be generated from the route name.')
                                                ->defaultNull()
                                            ->end()

                                            // ->arrayNode('parameters')
                                            //     ->info('Optional query/path/header parameters documentation. Used mainly for OpenAPI or generated API docs.')
                                            //     ->normalizeKeys(false)
                                            //     ->arrayPrototype()
                                            //         ->children()
                                            //             ->scalarNode('type')
                                            //                 ->info('Parameter type (e.g., int, string, boolean). Used for documentation.')
                                            //                 ->defaultNull()
                                            //             ->end()
                                            //             ->booleanNode('required')
                                            //                 ->info('Whether this parameter is required.')
                                            //                 ->defaultFalse()
                                            //             ->end()
                                            //             ->scalarNode('description')
                                            //                 ->info('Human-readable description of the parameter.')
                                            //                 ->defaultNull()
                                            //             ->end()
                                            //             ->scalarNode('location')
                                            //                 ->info('Optional parameter location: query, path, header. Default: query.')
                                            //                 ->defaultValue('query')
                                            //             ->end()
                                            //         ->end()
                                            //     ->end()
                                            // ->end()


                                            // ->arrayNode('responses')
                                            //     ->info('Describes possible HTTP responses for this endpoint, used for documentation and OpenAPI generation.')
                                            //     ->normalizeKeys(false)
                                            //     ->arrayPrototype()
                                            //         ->children()
                                            //             ->scalarNode('description')
                                            //                 ->info('Human-readable description of the response.')
                                            //                 ->isRequired()
                                            //                 ->cannotBeEmpty()
                                            //             ->end()
                                            //             ->arrayNode('schema')
                                            //                 ->info('Optional JSON schema or OpenAPI reference describing the response body structure.')
                                            //                 ->normalizeKeys(false)
                                            //                 ->variablePrototype()->end()
                                            //             ->end()
                                            //         ->end()
                                            //     ->end()
                                            // ->end()


                                        ->end()
                                    ->end()

                                    // ──────────────────────────────
                                    // Access control
                                    // ──────────────────────────────
                                    ->arrayNode('granted')
                                        ->info('Defines the access control rules for this endpoint: allowed roles, security expressions, or custom Symfony voters.')
                                        ->addDefaultsIfNotSet()
                                        ->children()

                                            ->arrayNode('roles')
                                                ->info('List of Symfony security roles required to access this endpoint, e.g., ["ROLE_ADMIN", "PUBLIC_ACCESS"].')
                                                ->scalarPrototype()->end()
                                                ->defaultValue(['PUBLIC_ACCESS'])
                                            ->end()

                                            ->scalarNode('voter')
                                                ->info('Optional custom voter FQCN. If set, Symfony will use this voter to determine access instead of roles or expressions.')
                                                ->defaultNull()
                                            ->end()

                                        ->end()
                                    ->end()

                                    // ──────────────────────────────
                                    // Hooks & Events
                                    // ──────────────────────────────
                                    ->arrayNode('hooks')
                                        ->info('Define event listeners to execute before or after the endpoint action. Useful for logging, auditing, validation, or custom side effects.')
                                        ->addDefaultsIfNotSet()
                                        ->children()

                                            ->arrayNode('before')
                                                ->info('List of callable listeners to execute **before** the endpoint action is run.')
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            ->arrayNode('after')
                                                ->info('List of callable listeners to execute **after** the endpoint action has completed.')
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                        ->end()

                                        ->validate()
                                            ->ifTrue(fn($hooks) => !HooksValidator::validate($hooks))
                                            ->thenInvalid('One or more hooks (before/after) are invalid. They must be valid callables (Class::method or callable).')
                                        ->end()
                                    ->end()

                                    // ──────────────────────────────
                                    // Serialization
                                    // ──────────────────────────────
                                    ->arrayNode('serialization')
                                        ->info('Configure how the endpoint response should be serialized, including Symfony serialization groups or custom transformers/normalizers.')
                                        ->addDefaultsIfNotSet()
                                        ->children()

                                            ->arrayNode('groups')
                                                ->info('List of Symfony serialization groups to apply when serializing the response for this endpoint.')
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            ->scalarNode('transformer')
                                                ->info('Optional class FQCN of a transformer or DTO to convert the entity data before serialization.')
                                                ->defaultNull()
                                                ->validate()
                                                    ->ifTrue(fn($v) => !TransformerValidator::isValid($v))
                                                    ->thenInvalid('The transformer class "%s" does not exist or does not implement __invoke() or transform() method.')
                                                ->end()
                                            ->end()

                                        ->end()
                                    ->end()

                                    // ──────────────────────────────
                                    // Transformer / DTOs
                                    // ──────────────────────────────
                                    ->scalarNode('transformer')
                                        ->info('Optional FQCN::method of a transformer or DTO to convert entity data before serialization.')
                                        ->defaultNull()
                                        ->validate()
                                            ->ifTrue(fn($v) => !TransformerValidator::isValid($v))
                                            ->thenInvalid('Invalid transformer: class or method does not exist.')
                                        ->end()
                                    ->end()

                                    // ──────────────────────────────
                                    // Rate limiting advanced (per role/user)
                                    // ──────────────────────────────
                                    ->arrayNode('rate_limit')
                                        ->addDefaultsIfNotSet()
                                        ->children()

                                            ->scalarNode('global')
                                                ->info('Default global rate limit for endpoint.')
                                                ->defaultNull()
                                            ->end()
                                        
                                            ->arrayNode('by_role')
                                                ->info('Rate limit per role, e.g. ROLE_ADMIN: 500/hour')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()
                                        
                                            ->arrayNode('by_user')
                                                ->info('Optional per-user rate limiting rules.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                        ->end()
                                    ->end()

                                ->end()
                            ->end()
                        ->end()

                    ->end() // of collections  arrayPrototype children
                ->end() // of collections arrayPrototype

                // Validation: entity existence
                ->validate()
                    ->ifTrue(fn($v) => EntityValidator::validateClassesExist(array_keys($v)))
                    ->thenInvalid('One or more entities defined in "api" do not exist. Check namespaces and spelling.')
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
            ApiVersionNumberResolver::resolve($providers);


            // ──────────────────────────────
            // Collections (Doctrine Entities)
            // ──────────────────────────────

            // Resolve the name of the collection App\\Entity\\Book → books
            CollectionNameResolver::resolve($providers);

            // Resolve default collection route name
            CollectionRouteNameResolver::default($providers);

            // Resolve collection route path prefix
            CollectionRoutePrefixResolver::default($providers);
            CollectionRoutePrefixResolver::resolve($providers);

            CollectionSearchStatusResolver::default($providers);

            foreach ($providers as $n => &$provider) 
            {
                foreach ($provider['collections'] as $entityName => &$collection)
                {

                    // COLLECTION ROUTE
                    // -- 


                    // COLLECTION SEARCH
                    // --

                    if (!isset($collection['pagination']) || $collection['pagination'] === null) 
                    {
                        $collection['pagination'] = $provider['pagination']['per_page'];
                    }







                    // 4. Normalize missing action fields
                    foreach ($collection['endpoints'] as $endpointName => &$endpoint) 
                    {
                        // ──────────────────────────────
                        // Route config
                        // ──────────────────────────────

                        // Route name
                        if (empty(trim($endpoint['route']['name'])))
                        {
                            $endpoint['route']['name'] = $collection['route']['name'];
                        }

                        // Generate Endpoint Route Name
                        $className = (new \ReflectionClass($entityName))->getShortName();
                        $className = strtolower($className);
                        $endpoint['route']['name'] = preg_replace("/{version}/", $provider['version']['number'], $endpoint['route']['name']);
                        $endpoint['route']['name'] = preg_replace("/{action}/", $endpointName, $endpoint['route']['name']);
                        $endpoint['route']['name'] = preg_replace("/{collection}/", $className, $endpoint['route']['name']);



                        // ──────────────────────────────
                        // Repository config
                        // ──────────────────────────────

                        // Route name
                        // if (empty(trim($endpoint['repository']['service'])))
                        // {
                        //     $endpoint['repository']['service'] = null;
                        // }

                        // ──────────────────────────────
                        // Metadata config
                        // ──────────────────────────────

                        // ──────────────────────────────
                        // Access control
                        // ──────────────────────────────



                    //     // Endpoint route requirements
                    //     if (!isset($endpoint['requirements'])) 
                    //     {
                    //         $endpoint['requirements'] = [];
                    //     }
                        
                        // Endpoint route defaults params values
                        // if (!isset($endpoint['defaults'])) 
                        // {
                        //     $endpoint['defaults'] = [];
                        // }
                        
                        // Endpoint route options
                    //     if (!isset($endpoint['options'])) 
                    //     {
                    //         $endpoint['options'] = [];
                    //     }
                        
                    //     // Endpoint route conditions
                    //     if (!isset($endpoint['conditions'])) 
                    //     {
                    //         $endpoint['conditions'] = '';
                    //     }
                        
                    //     // Endpoint route host
                    //     if (!isset($endpoint['host'])) 
                    //     {
                    //         $endpoint['host'] = '';
                    //     }
                        
                    //     // Endpoint route schemes
                    //     if (!isset($endpoint['schemes'])) 
                    //     {
                    //         $endpoint['schemes'] = [];
                    //     }

                    //     if (empty($endpoint['granted'])) 
                    //     {
                    //         $endpoint['granted'] = ['PUBLIC_ACCESS'];
                    //     }
                    //     if (!isset($endpoint['controller'])) 
                    //     {
                    //         $endpoint['controller'] = null;
                    //     }

                    }
                }
            }

            return $providers;
        })
    ->end(); // of Version generator
 };