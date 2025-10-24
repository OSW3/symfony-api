<?php

use OSW3\Api\Validator\HooksValidator;
use OSW3\Api\Validator\EntityValidator;
use OSW3\Api\Validator\ControllerValidator;
use OSW3\Api\Validator\TransformerValidator;
use OSW3\Api\Resolver\CollectionNameResolver;
use OSW3\Api\Resolver\ApiVersionNumberResolver;
use OSW3\Api\Resolver\EndpointRouteNameResolver;
use OSW3\Api\Resolver\EndpointRoutePathResolver;
use OSW3\Api\Resolver\EndpointTemplatesResolver;
use OSW3\Api\Resolver\EndpointUrlSupportResolver;
use OSW3\Api\Resolver\CollectionIsEnabledResolver;
use OSW3\Api\Resolver\CollectionTemplatesResolver;
use OSW3\Api\Resolver\EndpointUrlAbsoluteResolver;
use OSW3\Api\Resolver\EndpointUrlPropertyResolver;
use OSW3\Api\Resolver\EndpointRouteMethodsResolver;
use OSW3\Api\Resolver\EndpointRouteOptionsResolver;
use OSW3\Api\Resolver\CollectionRoutePrefixResolver;
use OSW3\Api\Resolver\EndpointRateLimitByIpResolver;
use OSW3\Api\Resolver\ApiVersionHeaderFormatResolver;
use OSW3\Api\Resolver\CollectionRoutePatternResolver;
use OSW3\Api\Resolver\CollectionSearchStatusResolver;
use OSW3\Api\Resolver\EndpointRateLimitLimitResolver;
use OSW3\Api\Resolver\CollectionRateLimitByIpResolver;
use OSW3\Api\Resolver\EndpointPaginationLimitResolver;
use OSW3\Api\Resolver\EndpointRateLimitByRoleResolver;
use OSW3\Api\Resolver\EndpointRateLimitByUserResolver;
use OSW3\Api\Resolver\CollectionRateLimitLimitResolver;
use OSW3\Api\Resolver\EndpointRateLimitEnabledResolver;
use OSW3\Api\Resolver\CollectionPaginationLimitResolver;
use OSW3\Api\Resolver\CollectionRateLimitByRoleResolver;
use OSW3\Api\Resolver\CollectionRateLimitByUserResolver;
use OSW3\Api\Resolver\EndpointPaginationEnabledResolver;
use OSW3\Api\Resolver\EndpointRouteRequirementsResolver;
use OSW3\Api\Resolver\CollectionRateLimitEnabledResolver;
use OSW3\Api\Resolver\EndpointPaginationMaxLimitResolver;
use OSW3\Api\Resolver\CollectionPaginationEnabledResolver;
use OSW3\Api\Resolver\CollectionPaginationMaxLimitResolver;
use OSW3\Api\Resolver\EndpointRateLimitByApplicationResolver;
use OSW3\Api\Resolver\EndpointRateLimitIncludeHeadersResolver;
use OSW3\Api\Resolver\CollectionRateLimitByApplicationResolver;
use OSW3\Api\Resolver\CollectionRateLimitIncludeHeadersResolver;
use OSW3\Api\Resolver\EndpointPaginationAllowLimitOverrideResolver;
use OSW3\Api\Resolver\CollectionPaginationAllowLimitOverrideResolver;

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
            // Enabled
            // ──────────────────────────────
            ->booleanNode('enabled')
                ->info('Enable or disable this provider.')
                ->defaultTrue()
                ->treatNullLike(true)
            ->end()

            // ──────────────────────────────
            // Deprecated
            // ──────────────────────────────
            ->booleanNode('deprecated')
                ->info('Whether this provider is deprecated.')
                ->defaultFalse()
                ->treatNullLike(false)
            ->end()

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

                ->end()
            ->end()

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
                        ->treatNullLike('v')
                    ->end()

                    ->enumNode('location')
                        ->info('How the version is exposed: in URL path, HTTP header, query parameter, or subdomain.')
                        ->values(['path', 'header', 'param', 'subdomain'])
                        ->defaultValue('path')
                        ->treatNullLike('path')
                    ->end()

                    ->scalarNode('header_format')
                        ->info('Defines the MIME type format used for API versioning via HTTP headers. Placeholders {vendor} and {version} will be replaced dynamically.')
                        ->defaultValue("application/vnd.{vendor}.{version}+json")
                        ->treatNullLike('application/vnd.{vendor}.{version}+json')
                    ->end()

                    ->booleanNode('beta')
                        ->info('Indicates whether this API version is in beta. If true, clients should be aware that the API may change.')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()

                    ->booleanNode('deprecated')
                        ->info('Indicates whether this API version is deprecated. If true, clients should migrate to a newer version.')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()
                    
                ->end()
            ->end()
            
            // ──────────────────────────────
            // Routes
            // ──────────────────────────────
			->arrayNode('routes')
                ->info('Default route naming and URL prefix for this API provider.')
                ->addDefaultsIfNotSet()->children()

                    ->scalarNode('pattern')
                        ->info('Pattern for route names. Available placeholders: {version}, {collection}, {action}.')
                        ->defaultValue('api:{version}:{collection}:{action}')
                        ->treatNullLike('api:{version}:{collection}:{action}')
                    ->end()

                    ->scalarNode('prefix')
                        ->info('Default URL prefix for all routes in this API version.')
                        ->defaultValue('/api/')
                        ->treatNullLike('/api/')
                    ->end()

                    ->arrayNode('hosts')
                        ->info('.')
                        ->normalizeKeys(false)
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()

                    ->arrayNode('schemes')
                        ->info('.')
                        ->normalizeKeys(false)
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()
                    
                ->end()
            ->end()

            // ──────────────────────────────
            // Pagination defaults
            // ──────────────────────────────
			->arrayNode('pagination')
                ->info('Default pagination behavior for all collections.')
                ->addDefaultsIfNotSet()->children()

                    ->booleanNode('enabled')
                        ->info('Enable or disable pagination for all collections.')
                        ->defaultTrue()
                        ->treatNullLike(true)
                    ->end()

                    ->integerNode('limit')
                        ->info('Limit the number of items returned per page.')
                        ->defaultValue(10)
                        ->treatNullLike(10)
                        ->min(1)
                    ->end()

                    ->integerNode('max_limit')
                        ->info('Maximum number of items returned per page.')
                        ->defaultValue(100)
                        ->treatNullLike(100)
                        ->min(1)
                    ->end()

                    ->booleanNode('allow_limit_override')
                        ->info('Allow overriding the "limit" parameter via URL (e.g. ?limit=50).')
                        ->defaultTrue()
                        ->treatNullLike(true)
                    ->end()

                ->end()
            ->end()

            // ──────────────────────────────
            // Search support
            // ──────────────────────────────
			->arrayNode('search')
                ->info('Global search configuration for all collections.')
                ->addDefaultsIfNotSet()->children()

                    ->booleanNode('enabled')
                        ->info('Enable or disable search globally for all collections.')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()

			    ->end()
            ->end()

            // ──────────────────────────────
            // URL support
            // ──────────────────────────────
			->arrayNode('url')
                ->info('URL Support (in response) for this API provider.')
                ->addDefaultsIfNotSet()->children()

                    ->booleanNode('support')
                        ->info('Whether to include URL elements in API responses.')
                        ->defaultTrue()
                        ->treatNullLike(true)
                    ->end()

                    ->booleanNode('absolute')
                        ->info('Generate absolute URLs if true, relative otherwise')
                        ->defaultTrue()
                        ->treatNullLike(true)
                    ->end()

                    ->scalarNode('property')
                        ->info('The name of the URL property in response.')
                        ->defaultValue('url')
                    ->end()

                ->end()
            ->end()

            // ──────────────────────────────
            // Rate Limit
            // ──────────────────────────────
			->arrayNode('rate_limit')
                ->info('Configuration for API rate limiting.')
                ->addDefaultsIfNotSet()->children()

                    ->booleanNode('enabled')
                        ->info('Enable or disable rate limiting for this API provider.')
                        ->defaultFalse()
                    ->end()

                    ->scalarNode('limit')
                        ->info('Maximum number of requests allowed in the specified time window.')
                        ->defaultValue('100/hour')
                        ->treatNullLike('100/hour')
                    ->end()

                    ->arrayNode('by_role')
                        ->info('Specific rate limits based on user roles.')
                        ->normalizeKeys(false)
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()

                    ->arrayNode('by_user')
                        ->info('Specific rate limits for individual users identified by user ID or username.')
                        ->normalizeKeys(false)
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()

                    ->arrayNode('by_ip')
                        ->info('Specific rate limits based on client IP addresses.')
                        ->normalizeKeys(false)
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()

                    ->arrayNode('by_application')
                        ->info('Specific rate limits for different application keys or API clients.')
                        ->normalizeKeys(false)
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()

                    ->booleanNode('include_headers')
                        ->info('Whether to include rate limit headers in responses.')
                        ->defaultTrue()
                    ->end()

                ->end()
            ->end()

            // ──────────────────────────────
            // Template
            // ──────────────────────────────
            ->arrayNode('templates')
                ->info('Paths to the response template files used as models for formatting the API output for lists and single items.')
                ->addDefaultsIfNotSet()->children()

                    ->scalarNode('list')
                        ->info('Path to the response template file used as a model for formatting the API output for lists.')
                        ->defaultValue('Resources/templates/list.yaml')
                        ->treatNullLike('Resources/templates/list.yaml')
                    ->end()

                    ->scalarNode('item')
                        ->info('Path to the response template file used as a model for formatting the API output for single items.')
                        ->defaultValue('Resources/templates/item.yaml')
                        ->treatNullLike('Resources/templates/item.yaml')
                    ->end()

                    ->scalarNode('error')
                        ->info('Path to the response template file used as a model for formatting error responses.')
                        ->defaultValue('Resources/templates/error.yaml')
                        ->treatNullLike('Resources/templates/error.yaml')
                    ->end()

                    ->scalarNode('not_found')
                        ->info('Path to the response template file used as a model for formatting not found responses (e.g. 404 Not Found).')
                        ->defaultValue('Resources/templates/not_found.yaml')
                        ->treatNullLike('Resources/templates/not_found.yaml')
                    ->end()

                ->end()
            ->end()

            // ──────────────────────────────
            // Response 
            // ──────────────────────────────
			->arrayNode('response')
                ->info('Settings related to API response formatting, including templates, default format, caching, and headers.')
                ->addDefaultsIfNotSet()->children()

                    ->enumNode('format')
                        ->info('Default response format if not specified by the client via Accept header or URL extension.')
                        ->values(['json', 'xml', 'yaml'])
                        ->defaultValue('json')
                        ->treatNullLike('json')
                    ->end()

                    ->booleanNode('allow_format_override')
                        ->info('If true, allows clients to override the response format using a URL parameter (e.g. ?format=xml).')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()

                    ->arrayNode('checksum')
                        ->info('Configuration for response checksum/hash settings.')
                        ->addDefaultsIfNotSet()->children()

                            ->booleanNode('enabled')
                                ->info('If true, enables response checksum/hash verification.')
                                ->defaultTrue()
                                ->treatNullLike(true)
                            ->end()

                            ->enumNode('algorithm')
                                ->info('Hash algorithm to use for response hashing. Options include "md5", "sha1", "sha256", etc.')
                                ->values(['md5', 'sha1', 'sha256', 'sha512'])
                                ->defaultValue('sha256')
                                ->treatNullLike('sha256')
                            ->end()

                        ->end()
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

                    ->arrayNode('compression')
                    ->info('Configuration for response compression settings.')
                    ->addDefaultsIfNotSet()->children()

                        ->booleanNode('enable')
                            ->info('Enable or disable response compression.')
                            ->defaultFalse()
                        ->end()

                        ->enumNode('format')
                            ->info('Compression format to use.')
                            ->defaultValue('gzip')
                            ->values(['gzip', 'deflate', 'brotli'])
                        ->end()

                        ->integerNode('level')
                            ->info('Compression level (0-9) for the selected format.')
                            ->defaultValue(6)
                            ->min(0)
                            ->max(9)
                        ->end()

                    ->end()->end()

                ->end()
            ->end()

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
            // Security
            // ──────────────────────────────
			->arrayNode('security')
            ->info('Defines security settings for the API.')
            ->addDefaultsIfNotSet()->children()

                ->arrayNode('entity')
                ->info('Defines the user entity used for authentication and authorization.')
                ->addDefaultsIfNotSet()->children()

                    ->scalarNode('class')
                        ->info('Fully qualified class name of the User entity used for authentication and authorization.')
                        ->defaultNull()
                        ->validate()
                            ->ifTrue(fn($class) => $class !== null && !EntityValidator::isValid($class))
                            ->thenInvalid('Invalid entity class "%s".', 'entity.class')
                        ->end()
                    ->end()

                ->end()->end()

                ->arrayNode('routes')
                ->info('Defines the security routes settings for the API.')
                ->addDefaultsIfNotSet()->children()

                    ->scalarNode('collection')
                        ->info('Name of the collection used in the URL path for registration and login endpoints.')
                        ->defaultValue('security')
                    ->end()

                ->end()->end()

                ->arrayNode('register')
                ->info('Defines the registration settings for the API.')
                ->addDefaultsIfNotSet()->children()

                    ->booleanNode('enable')
                        ->info('Enable or disable registration.')
                        ->defaultFalse()
                    ->end()

                    ->scalarNode('method')
                        ->info('HTTP method to use for the registration endpoint.')
                        ->defaultValue('POST')
                    ->end()

                    ->scalarNode('path')
                        ->info('Path for the registration endpoint.')
                        ->defaultNull()
                    ->end()

                    ->scalarNode('controller')
                        ->info('Optional Symfony controller (FQCN::method) to handle registration. If not defined, the default RegisterController will be used.')
                        // ->cannotBeEmpty()
                        ->defaultValue('OSW3\Api\Controller\RegisterController::register')
                        ->validate()
                            ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                            ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.', 'register.controller')
                        ->end()
                    ->end()

                    ->arrayNode('properties')
                        ->info('Maps request properties to entity fields during login.')
                        ->normalizeKeys(false)
                        ->scalarPrototype()->end()
                        ->defaultValue(['username' => 'email', 'password' => 'password'])
                    ->end()

                ->end()->end()

                ->arrayNode('login')
                ->info('Defines the login settings for the API.')
                ->addDefaultsIfNotSet()->children()

                    ->booleanNode('enable')
                        ->info('Enable or disable login.')
                        ->defaultFalse()
                    ->end()

                    ->scalarNode('method')
                        ->info('HTTP method to use for the login endpoint.')
                        ->defaultValue('POST')
                    ->end()

                    ->scalarNode('path')
                        ->info('Path for the registration endpoint.')
                        ->defaultNull()
                    ->end()

                    ->scalarNode('controller')
                        ->info('Optional Symfony controller (FQCN::method) to handle registration. If not defined, the default SecurityController will be used.')
                        // ->cannotBeEmpty()
                        ->defaultValue('OSW3\Api\Controller\SecurityController::login')
                        ->validate()
                            ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                            ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.', 'login.controller')
                        ->end()
                    ->end()

                    ->arrayNode('properties')
                        ->info('Maps request properties to entity fields during login.')
                        ->normalizeKeys(false)
                        ->scalarPrototype()->end()
                        ->defaultValue(['username' => 'email', 'password' => 'password'])
                    ->end()

                ->end()->end()

            ->end()->end()

            // ──────────────────────────────
            // Debug
            // ──────────────────────────────
			->arrayNode('debug')
                ->info('Debug configuration')
                ->addDefaultsIfNotSet()->children()

                    ->booleanNode('enable')
                        ->info('Enable or disable debug.')
                        ->defaultTrue()
                    ->end()

                // 	->booleanNode('enable')
                //         ->info('Enable or disable tracing.')
                //         ->defaultTrue()
                //     ->end()

                // 	->booleanNode('request')
                //         ->info('Enable or disable request_id.')
                //         ->defaultTrue()
                //     ->end()

			    ->end()
            ->end()

            
            // ──────────────────────────────
            // Collections (Doctrine Entities)
            // ──────────────────────────────
			->arrayNode('collections')
                ->info('List of Doctrine entity classes to expose as REST collections.')
                ->useAttributeAsKey('entity')
                ->arrayPrototype()
                // ->ignoreExtraKeys(false)
                    ->children()

                        // ──────────────────────────────
                        // Enabled
                        // ──────────────────────────────
                        ->booleanNode('enabled')
                            ->info('Enable or disable this provider.')
                            ->defaultNull()
                            // ->defaultTrue()
                            // ->treatNullLike(null)
                        ->end()

                        // ──────────────────────────────
                        // Deprecated
                        // ──────────────────────────────
                        ->booleanNode('deprecated')
                            ->info('Whether this collection is deprecated.')
                            ->defaultFalse()
                            ->treatNullLike(false)
                        ->end()

                        // ──────────────────────────────
                        // Collection name
                        // ──────────────────────────────
                        ->scalarNode('name')
                            ->info('Collection name in URLs and route names. Auto-generated from entity if null (e.g. App\\Entity\\Book → books).')
                            ->defaultNull()
                        ->end()

                        // ──────────────────────────────
                        // Route
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

                                ->arrayNode('hosts')
                                    ->info('Configure specific hosts for the collection routes.')
                                    ->normalizeKeys(false)
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()

                                ->arrayNode('schemes')
                                    ->info('Configure specific schemes (http, https) for the collection routes.')
                                    ->normalizeKeys(false)
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()
                            
                            ->end()
                        ->end()

                        // ──────────────────────────────
                        // Search
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
                        // Pagination
                        // ──────────────────────────────
                        ->arrayNode('pagination')
                            ->info('Default pagination behavior for a specific collection.')
                            ->addDefaultsIfNotSet()->children()

                                ->booleanNode('enabled')
                                    ->info('Enable or disable pagination for this collection.')
                                    ->defaultTrue()
                                    ->treatNullLike(true)
                                ->end()

                                ->integerNode('limit')
                                    ->info('Limit the number of items per page for this collection.')
                                    ->defaultValue(10)
                                    ->treatNullLike(-1)
                                ->end()

                                ->integerNode('max_limit')
                                    ->info('Max number of items per page for this collection.')
                                    ->defaultValue(100)
                                    ->treatNullLike(-1)
                                ->end()

                                ->booleanNode('allow_limit_override')
                                    ->info('Allow overriding the "limit" parameter via URL (e.g. ?limit=50) for this collection.')
                                    ->defaultTrue()
                                    ->treatNullLike(true)
                                ->end()

                            ->end()
                        ->end()

                        // ──────────────────────────────
                        // URL support
                        // ──────────────────────────────
                        ->arrayNode('url')
                            ->info('URL Support (in response) for this collection.')
                            ->addDefaultsIfNotSet()->children()

                                ->booleanNode('support')
                                    ->info('Whether to include URL elements in API responses.')
                                    ->defaultNull()
                                ->end()

                                ->booleanNode('absolute')
                                    ->info('Generate absolute URLs if true, relative otherwise')
                                    ->defaultNull()
                                ->end()

                                ->scalarNode('property')
                                    ->info('The name of the URL property in response.')
                                    ->defaultNull()
                                ->end()

                            ->end()
                        ->end()

                        // ──────────────────────────────
                        // Rate Limit
                        // ──────────────────────────────
                        ->arrayNode('rate_limit')
                            ->info('Configuration for API rate limiting.')
                            ->addDefaultsIfNotSet()->children()

                                ->booleanNode('enabled')
                                    ->info('Enable or disable rate limiting for this API provider.')
                                    ->defaultFalse()
                                ->end()

                                ->scalarNode('limit')
                                    ->info('Maximum number of requests allowed in the specified time window.')
                                    ->defaultValue('100/hour')
                                ->end()

                                ->arrayNode('by_role')
                                    ->info('Specific rate limits based on user roles.')
                                    ->normalizeKeys(false)
                                    // ->scalarPrototype()->end()
                                    // ->defaultValue([])
                                ->end()

                                ->arrayNode('by_user')
                                    ->info('Specific rate limits for individual users identified by user ID or username.')
                                    ->normalizeKeys(false)
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()

                                ->arrayNode('by_ip')
                                    ->info('Specific rate limits based on client IP addresses.')
                                    ->normalizeKeys(false)
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()

                                ->arrayNode('by_application')
                                    ->info('Specific rate limits for different application keys or API clients.')
                                    ->normalizeKeys(false)
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()

                                ->booleanNode('include_headers')
                                    ->info('Whether to include rate limit headers in responses.')
                                    ->defaultTrue()
                                ->end()

                            ->end()
                        ->end()

                        // ──────────────────────────────
                        // Template
                        // ──────────────────────────────
                        ->arrayNode('templates')
                            ->info('Paths to the response template files used as models for formatting the API output for lists and single items.')
                            ->addDefaultsIfNotSet()->children()

                                ->scalarNode('list')
                                    ->info('Path to the response template file used as a model for formatting the API output for lists.')
                                    ->defaultNull()
                                ->end()

                                ->scalarNode('item')
                                    ->info('Path to the response template file used as a model for formatting the API output for single items.')
                                    ->defaultNull()
                                ->end()

                                ->scalarNode('error')
                                    ->info('Path to the response template file used as a model for formatting error responses.')
                                    ->defaultNull()
                                ->end()

                                ->scalarNode('not_found')
                                    ->info('Path to the response template file used as a model for formatting not found responses (e.g. 404 Not Found).')
                                    ->defaultNull()
                                ->end()

                            ->end()
                        ->end()


                        // ──────────────────────────────
                        // REST endpoints
                        // ──────────────────────────────
                        ->arrayNode('endpoints')
                            ->info('Configure the endpoints available for this collection. Default: index, create, read, update, delete.')
                            ->useAttributeAsKey('endpoint')  
                            ->requiresAtLeastOneElement()
                            ->arrayPrototype()
                            // ->ignoreExtraKeys(false)
                                ->children()

                                    // ──────────────────────────────
                                    // Enabled
                                    // ──────────────────────────────
                                    ->booleanNode('enabled')
                                        ->info('Enable or disable this endpoint.')
                                        ->defaultNull()
                                        // ->defaultTrue()
                                        // ->treatNullLike(true)
                                    ->end()

                                    // ──────────────────────────────
                                    // Deprecated
                                    // ──────────────────────────────
                                    ->booleanNode('deprecated')
                                        ->info('Whether this endpoint is deprecated.')
                                        ->defaultFalse()
                                        ->treatNullLike(false)
                                    ->end()

                                    // ──────────────────────────────
                                    // Route config
                                    // ──────────────────────────────
                                    ->arrayNode('route')
                                        ->info('Defines the HTTP configuration for the endpoint: route name, path, HTTP methods, controller, constraints, and routing options.')
                                        // ->isRequired()
                                        ->addDefaultsIfNotSet()
                                        ->children()

                                            ->scalarNode('pattern')
                                                ->info('Custom route name pattern. Falls back to global `routes.name` if null.')
                                                ->defaultNull()
                                            ->end()

                                            ->scalarNode('name')
                                                ->info('Route name. If not defined, it will be generated automatically based on the collection and endpoint name.')
                                                ->defaultNull()
                                            ->end()

                                            ->scalarNode('path')
                                                ->info('Optional custom path for this endpoint.')
                                                ->defaultNull()
                                            ->end()

                                            ->arrayNode('methods')
                                                ->info('Allowed HTTP methods. Must be explicitly defined to avoid accidental exposure.')
                                                // ->requiresAtLeastOneElement()
                                                // ->isRequired()
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
                                                ->defaultValue([])
                                            ->end()

                                            ->arrayNode('options')
                                                ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            ->scalarNode('condition')
                                                ->info('Optional condition expression for the route.')
                                                ->defaultNull()
                                            ->end()

                                            ->arrayNode('hosts')
                                                ->info('Configure specific hosts for the endpoint routes.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            ->arrayNode('schemes')
                                                ->info('Configure specific schemes (http, https) for the endpoint routes.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                        ->end()
                                    ->end()

                                    // ──────────────────────────────
                                    // Pagination config
                                    // ──────────────────────────────
                                    ->arrayNode('pagination')
                                        ->info('Pagination settings for a specific endpoint.')
                                        ->addDefaultsIfNotSet()->children()

                                            ->booleanNode('enabled')
                                                ->info('Enable or disable pagination for this endpoint.')
                                                ->defaultTrue()
                                                ->treatNullLike(true)
                                            ->end()

                                            ->integerNode('limit')
                                                ->info('Limit the number of items per page for this endpoint.')
                                                ->defaultValue(10)
                                                ->treatNullLike(-1)
                                            ->end()

                                            ->integerNode('max_limit')
                                                ->info('Max number of items per page for this endpoint.')
                                                ->defaultValue(100)
                                                ->treatNullLike(-1)
                                            ->end()

                                            ->booleanNode('allow_limit_override')
                                                ->info('Allow overriding the "limit" parameter via URL (e.g. ?limit=50) for this endpoint.')
                                                ->defaultTrue()
                                                ->treatNullLike(true)
                                            ->end()

                                        ->end()
                                    ->end()

                                    // ──────────────────────────────
                                    // Rate Limit
                                    // ──────────────────────────────
                                    ->arrayNode('rate_limit')
                                        ->info('Configuration for API rate limiting.')
                                        ->addDefaultsIfNotSet()->children()

                                            ->booleanNode('enabled')
                                                ->info('Enable or disable rate limiting for this API provider.')
                                                ->defaultFalse()
                                            ->end()

                                            ->scalarNode('limit')
                                                ->info('Maximum number of requests allowed in the specified time window.')
                                                ->defaultValue('100/hour')
                                            ->end()

                                            ->arrayNode('by_role')
                                                ->info('Specific rate limits based on user roles.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            ->arrayNode('by_user')
                                                ->info('Specific rate limits for individual users identified by user ID or username.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            ->arrayNode('by_ip')
                                                ->info('Specific rate limits based on client IP addresses.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            ->arrayNode('by_application')
                                                ->info('Specific rate limits for different application keys or API clients.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            ->booleanNode('include_headers')
                                                ->info('Whether to include rate limit headers in responses.')
                                                ->defaultTrue()
                                            ->end()

                                        ->end()
                                    ->end()

                                    // ──────────────────────────────
                                    // Template
                                    // ──────────────────────────────
                                    ->arrayNode('templates')
                                        ->info('Paths to the response template files used as models for formatting the API output for lists and single items.')
                                        ->addDefaultsIfNotSet()->children()

                                            ->scalarNode('list')
                                                ->info('Path to the response template file used as a model for formatting the API output for lists.')
                                                ->defaultNull()
                                            ->end()

                                            ->scalarNode('item')
                                                ->info('Path to the response template file used as a model for formatting the API output for single items.')
                                                ->defaultNull()
                                            ->end()

                                            ->scalarNode('error')
                                                ->info('Path to the response template file used as a model for formatting error responses.')
                                                ->defaultNull()
                                            ->end()

                                            ->scalarNode('not_found')
                                                ->info('Path to the response template file used as a model for formatting not found responses (e.g. 404 Not Found).')
                                                ->defaultNull()
                                            ->end()

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

                                            // ->scalarNode('summary')
                                            //     ->info('A brief one-line summary for OpenAPI documentation. Useful when "description" is longer.')
                                            //     ->defaultNull()
                                            // ->end()

                                            ->booleanNode('internal_only')
                                                ->info('If true, marks the endpoint as internal (not exposed in public documentation or API discovery)s.')
                                                ->defaultFalse()
                                            ->end()

                                            ->scalarNode('tags') // TODO: arrayNode ?
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
            
            // Enabled
            // ProviderIsEnabledResolver::default($providers);
            
            // 1. Generate missing versions
            ApiVersionNumberResolver::resolve($providers);
            ApiVersionHeaderFormatResolver::resolve($providers);


            // ──────────────────────────────
            // Collections (Doctrine Entities)
            // ──────────────────────────────

            // Enabled
            CollectionIsEnabledResolver::default($providers);

            // Name
            CollectionNameResolver::resolve($providers);

            // Route
            CollectionRoutePatternResolver::default($providers);
            CollectionRoutePrefixResolver::default($providers);
            CollectionRoutePrefixResolver::resolve($providers);

            // Search
            CollectionSearchStatusResolver::default($providers);

            // Pagination
            CollectionPaginationEnabledResolver::default($providers);
            CollectionPaginationLimitResolver::default($providers);
            CollectionPaginationMaxLimitResolver::default($providers);
            CollectionPaginationAllowLimitOverrideResolver::default($providers);

            // Rate Limit
            CollectionRateLimitEnabledResolver::default($providers);
            CollectionRateLimitLimitResolver::default($providers);
            CollectionRateLimitByRoleResolver::default($providers);
            CollectionRateLimitByUserResolver::default($providers);
            CollectionRateLimitByIpResolver::default($providers);
            CollectionRateLimitByApplicationResolver::default($providers);
            CollectionRateLimitIncludeHeadersResolver::default($providers);

            // Templates
            CollectionTemplatesResolver::default($providers);


            // ──────────────────────────────
            // REST endpoints
            // ──────────────────────────────

            // Enabled
            // EndpointEnabledResolver::default($providers);

            // Route
            EndpointRouteNameResolver::default($providers);
            EndpointRouteNameResolver::resolve($providers);
            EndpointRoutePathResolver::default($providers);
            EndpointRoutePathResolver::resolve($providers);
            EndpointRouteMethodsResolver::resolve($providers);
            EndpointRouteRequirementsResolver::resolve($providers);
            EndpointRouteOptionsResolver::resolve($providers);

            // Pagination
            EndpointPaginationEnabledResolver::default($providers);
            EndpointPaginationLimitResolver::default($providers);
            EndpointPaginationMaxLimitResolver::default($providers);
            EndpointPaginationAllowLimitOverrideResolver::default($providers);

            // URL Support
            EndpointUrlSupportResolver::default($providers);
            EndpointUrlAbsoluteResolver::default($providers);
            EndpointUrlPropertyResolver::default($providers);

            // Rate Limit
            EndpointRateLimitEnabledResolver::default($providers);
            EndpointRateLimitLimitResolver::default($providers);
            EndpointRateLimitByRoleResolver::default($providers);
            EndpointRateLimitByUserResolver::default($providers);
            EndpointRateLimitByIpResolver::default($providers);
            EndpointRateLimitByApplicationResolver::default($providers);
            EndpointRateLimitIncludeHeadersResolver::default($providers);

            // Templates
            EndpointTemplatesResolver::default($providers);


            return $providers;
        })
    ->end(); // of Version generator
 };