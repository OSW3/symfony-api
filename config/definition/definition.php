<?php

use OSW3\Api\Validator\HooksValidator;
use OSW3\Api\Validator\EntityValidator;
use OSW3\Api\Validator\ControllerValidator;
use OSW3\Api\Validator\TransformerValidator;
use OSW3\Api\Resolver\CollectionNameResolver;
use OSW3\Api\Resolver\ApiVersionNumberResolver;
use OSW3\Api\Resolver\EndpointRouteNameResolver;
use OSW3\Api\Resolver\CollectionPaginationResolver;
use OSW3\Api\Resolver\CollectionRoutePrefixResolver;
use OSW3\Api\Resolver\ApiVersionHeaderFormatResolver;
use OSW3\Api\Resolver\CollectionRoutePatternResolver;
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
            // Documentation
            // ──────────────────────────────
			->arrayNode('documentation')
            ->info('API documentation configuration')
            ->addDefaultsIfNotSet()->children()

                ->booleanNode('enable')
                    ->info('Enable or disable the documentation for this API provider.')
                    ->defaultFalse()
                ->end()

                ->scalarNode('prefix')
                    ->info('Path prefix')
                    ->defaultValue('_documentation')
                ->end()

            ->end()->end()

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

                ->enumNode('location')
                    ->info('How the version is exposed: in URL path, HTTP header, query parameter, or subdomain.')
                    ->values(['path', 'header', 'param', 'subdomain'])
                    ->defaultValue('path')
                ->end()

                ->scalarNode('header_format')
                    ->info('Defines the MIME type format used for API versioning via HTTP headers. Placeholders {vendor} and {version} will be replaced dynamically.')
                    ->defaultValue("application/vnd.{vendor}.{version}+json")
                ->end()

                ->booleanNode('beta')
                    ->info('Indicates whether this API version is in beta. If true, clients should be aware that the API may change.')
                    ->defaultFalse()
                ->end()

                ->booleanNode('deprecated')
                    ->info('Indicates whether this API version is deprecated. If true, clients should migrate to a newer version.')
                    ->defaultFalse()
                ->end()
                
            ->end()->end()
            
            // ──────────────────────────────
            // Global route settings
            // ──────────────────────────────
			->arrayNode('routes')
            ->info('Default route naming and URL prefix for this API provider.')
            ->addDefaultsIfNotSet()->children()

                ->scalarNode('pattern')
                    ->info('Pattern for route names. Available placeholders: {version}, {collection}, {action}.')
                    ->defaultValue('api:{version}:{collection}:{action}')
                ->end()

                ->scalarNode('prefix')
                    ->info('Default URL prefix for all routes in this API version.')
                    ->defaultValue('/api/')
                ->end()

                ->arrayNode('hosts')
                    ->info('.')
                    // ->defaultValue([])
                ->end()

                ->arrayNode('schemes')
                    ->info('.')
                    // ->defaultValue([])
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
            // Debug
            // ──────────────────────────────
			->arrayNode('debug')
            ->info('.')
            ->addDefaultsIfNotSet()->children()

				->booleanNode('enable')
                    ->info('Enable or disable debug.')
                    ->defaultTrue()
                ->end()

			->end()->end()

            // ──────────────────────────────
            // Debug
            // ──────────────────────────────
			->arrayNode('tracing')
            ->info('.')
            ->addDefaultsIfNotSet()->children()

				->booleanNode('enable')
                    ->info('Enable or disable tracing.')
                    ->defaultTrue()
                ->end()

				->booleanNode('request')
                    ->info('Enable or disable request_id.')
                    ->defaultTrue()
                ->end()

			->end()->end()

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

				->integerNode('limit')
                    ->info('Default number of items per page.')
                    ->defaultValue(10)
                    ->min(1)
                ->end()

				->integerNode('max_limit')
                    ->info('Max number of items per page.')
                    ->defaultValue(100)
                    ->min(1)
                ->end()

                ->booleanNode('allow_limit_override')
                    ->info('Allow overriding the "limit" parameter via URL (e.g. ?limit=50).')
                    ->defaultTrue()
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

                ->scalarNode('property')
                    ->info('The name of the URL property in response.')
                    ->defaultValue('url')
                ->end()

			->end()->end()

            // ──────────────────────────────
            // Response formatting 
            // ──────────────────────────────
			->arrayNode('response')
            ->info('Settings related to API response formatting, including templates, default format, caching, and headers.')
            ->addDefaultsIfNotSet()->children()

                ->arrayNode('templates')
                ->info('Paths to the response template files used as models for formatting the API output for lists and single items.')
                ->addDefaultsIfNotSet()->children()

                    ->scalarNode('list')
                        ->info('Path to the response template file used as a model for formatting the API output for lists.')
                        ->defaultValue('Resources/templates/list.yaml')
                    ->end()

                    ->scalarNode('item')
                        ->info('Path to the response template file used as a model for formatting the API output for single items.')
                        ->defaultValue('Resources/templates/item.yaml')
                    ->end()

                    ->scalarNode('error')
                        ->info('Path to the response template file used as a model for formatting error responses.')
                        ->defaultValue('Resources/templates/error.yaml')
                    ->end()

                    ->scalarNode('no_content')
                        ->info('Path to the response template file used as a model for formatting no content responses (e.g. 204 No Content).')
                        ->defaultValue('Resources/templates/no_content.yaml')
                    ->end()

                ->end()->end()

                ->enumNode('format')
                    ->info('Default response format if not specified by the client via Accept header or URL extension.')
                    ->values(['json', 'xml', 'yaml'])
                    ->defaultValue('json')
                ->end()

                ->arrayNode('cache_control')
                ->info('Defines HTTP caching behavior for API responses, including Cache-Control directives and related headers.')
                ->addDefaultsIfNotSet()->children()

                    ->booleanNode('public')
                        ->info('If true, sets Cache-Control to "public", allowing shared caches. If false, sets to "private".')
                        ->defaultTrue()
                    ->end()

                    ->booleanNode('no_store')
                        ->info('If true, adds "no-store" to Cache-Control.')
                        ->defaultFalse()
                    ->end()

                    ->booleanNode('must_revalidate')
                        ->info('If true, adds "must-revalidate" to Cache-Control.')
                        ->defaultTrue()
                    ->end()

                    ->integerNode('max_age')
                        ->info('Max age in seconds (0 = no cache).')
                        ->defaultValue(3600)
                    ->end()

                ->end()->end()

                ->arrayNode('headers')
                ->info('.')
                ->addDefaultsIfNotSet()->children()

                    ->arrayNode('expose')
                        ->info('List of headers to expose via CORS.')
                        ->scalarPrototype()->end()
                        ->defaultValue(['Content-Type', 'Authorization', 'X-Requested-With', 'API-Version', 'X-RateLimit-Limit', 'X-RateLimit-Remaining', 'X-RateLimit-Reset'])
                    ->end()

                    ->arrayNode('allow')
                        ->info('List of headers allowed in CORS requests.')
                        ->scalarPrototype()->end()
                        ->defaultValue(['Content-Type', 'Authorization', 'X-Requested-With', 'API-Version'])
                    ->end()

                    ->arrayNode('vary')
                        ->info('List of headers to include in the Vary response header.')
                        ->scalarPrototype()->end()
                        ->defaultValue(['Accept', 'API-Version'])  
                    ->end()

                    ->arrayNode('cache_control')
                        ->info('Default Cache-Control directives to include in responses.')
                        ->scalarPrototype()->end()
                        ->defaultValue(['no-cache', 'no-store', 'must-revalidate']) 
                    ->end()

                    ->arrayNode('custom')
                        ->info('Custom headers to always include in responses. Key is header name, value is header value.')
                        ->normalizeKeys(false)
                        ->scalarPrototype()->end()
                        ->defaultValue(['X-Powered-By' => 'OSW3 Api'])
                    ->end() 

                    ->arrayNode('remove')
                        ->info('List of headers to remove from responses.')
                        ->scalarPrototype()->end()
                        ->defaultValue(['X-Powered-By'])
                    ->end()

                ->end()->end()

                ->enumNode('algorithm')
                    ->info('Hash algorithm to use for response hashing. Options include "md5", "sha1", "sha256", etc.')
                    ->values(['md5', 'sha1', 'sha256', 'sha512'])
                    ->defaultValue('md5')
                ->end()

			->end()->end()

            // ──────────────────────────────
            // Rate Limit
            // ──────────────────────────────
			->arrayNode('rate_limit')
            ->info('.')
            ->addDefaultsIfNotSet()->children()

                ->booleanNode('enable')
                    ->info('Enable or disable rate limiting for this API provider.')
                    ->defaultFalse()
                ->end()

                ->scalarNode('limit')
                    ->info('Maximum number of requests allowed in the defined period.')
                    ->defaultValue('1000')
                ->end()

                ->enumNode('scope')
                    ->info('Defines the scope of the rate limit: by IP address, authenticated user, or application. "ip" limits per client IP, "user" limits per logged-in user, "app" limits per API key or application.')
                    ->defaultValue('user')
                    ->values(['ip', 'user', 'app'])
                ->end()

                ->scalarNode('period')
                    ->info('Time period for the rate limit (e.g. "hour", "minute", "day").')
                    ->defaultValue('day')
                ->end()

                ->booleanNode('include_headers')
                    ->info('Whether to include rate limit headers in responses.')
                    ->defaultTrue()
                ->end()

            ->end()->end()

            // ──────────────────────────────
            // Serialization
            // ──────────────────────────────
            ->arrayNode('serialization')
                ->info('Defines serialization settings applied to API responses, including ignored attributes, date formatting, and null value handling.')
                ->addDefaultsIfNotSet()
                ->children()

                    ->arrayNode('ignore')
                        ->info('List of attributes to exclude from the response.')
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()

                    ->arrayNode('datetime')
                        ->info('Controls how datetime objects are formatted during serialization.')
                        ->addDefaultsIfNotSet()
                        ->children()

                            ->scalarNode('format')
                                ->info('Date/time output format (e.g. "Y-m-d H:i:s" or ISO 8601). Set to null to use Symfony’s default format.')
                                ->defaultValue('Y-m-d H:i:s')
                            ->end()

                            ->scalarNode('timezone')
                                ->info('Timezone applied when serializing datetime values (e.g. "UTC", "Europe/Paris"). Set to null to use the system default.')
                                ->defaultValue('UTC')
                            ->end()

                        ->end()
                    ->end()

                    ->booleanNode('skip_null')
                        ->info('If true, fields with null values are omitted from the serialized response.')
                        ->defaultFalse()
                    ->end()

                ->end()
            ->end()
            
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

                                ->scalarNode('pattern')
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
                        ->scalarNode('pagination')
                            ->info('Override pagination items per page for this collection.')
                            ->defaultNull()
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

                                            ->arrayNode('on_success')
                                                ->info('List of callable listeners to execute on success action.')
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            ->arrayNode('on_error')
                                                ->info('List of callable listeners to execute on error action.')
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

                                            ->arrayNode('ignore')
                                                ->info('List of entity attributes or properties to explicitly exclude from serialization.')
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
            

            // ApiVendorResolver::resolve($providers);

            // 1. Generate missing versions
            ApiVersionNumberResolver::resolve($providers);
            ApiVersionHeaderFormatResolver::resolve($providers);


            // ──────────────────────────────
            // Collections (Doctrine Entities)
            // ──────────────────────────────

            // Resolve the name of the collection App\\Entity\\Book → books
            CollectionNameResolver::resolve($providers);

            // Resolve default collection route name
            CollectionRoutePatternResolver::default($providers);

            // Resolve collection route path prefix
            CollectionRoutePrefixResolver::default($providers);
            CollectionRoutePrefixResolver::resolve($providers);

            // Resolve collection search
            CollectionSearchStatusResolver::default($providers);

            // Resolve collection pagination (per page)
            CollectionPaginationResolver::default($providers);

            
            // ──────────────────────────────
            // REST endpoints
            // ──────────────────────────────

            EndpointRouteNameResolver::default($providers);
            EndpointRouteNameResolver::resolve($providers);




            foreach ($providers as $providerName => &$provider) {
                foreach ($provider['collections'] as $collectionName => &$collection) {
                    foreach ($collection['endpoints'] as $endpointName => &$endpoint) {

                        // EndpointRouteNameResolver::default($collection, $endpoint);
                        // EndpointRouteNameResolver::resolve($provider, $endpoint, $endpointName, $collectionName);
                        



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