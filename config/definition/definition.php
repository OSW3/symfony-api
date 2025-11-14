<?php

use OSW3\Api\Enum\MimeType;
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
use OSW3\Api\Resolver\EndpointRateLimitLimitResolver;
use OSW3\Api\Resolver\CollectionRateLimitByIpResolver;
use OSW3\Api\Resolver\EndpointPaginationLimitResolver;
use OSW3\Api\Resolver\EndpointRateLimitByRoleResolver;
use OSW3\Api\Resolver\EndpointRateLimitByUserResolver;
use OSW3\Api\Resolver\EndpointRouteControllerResolver;
use OSW3\Api\Resolver\CollectionRateLimitLimitResolver;
use OSW3\Api\Resolver\EndpointRateLimitEnabledResolver;
use OSW3\Api\Resolver\CollectionPaginationLimitResolver;
use OSW3\Api\Resolver\CollectionRateLimitByRoleResolver;
use OSW3\Api\Resolver\CollectionRateLimitByUserResolver;
use OSW3\Api\Resolver\EndpointPaginationEnabledResolver;
use OSW3\Api\Resolver\EndpointRouteRequirementsResolver;
use OSW3\Api\Resolver\CollectionRateLimitEnabledResolver;
use OSW3\Api\Resolver\EndpointDeprecationEnabledResolver;
use OSW3\Api\Resolver\EndpointDeprecationStartAtResolver;
use OSW3\Api\Resolver\EndpointPaginationMaxLimitResolver;
use OSW3\Api\Resolver\ProviderDeprecationStartAtResolver;
use OSW3\Api\Resolver\CollectionPaginationEnabledResolver;
use OSW3\Api\Resolver\EndpointDeprecationSunsetAtResolver;
use OSW3\Api\Resolver\ProviderDeprecationSunsetAtResolver;
use OSW3\Api\Resolver\CollectionDeprecationEnabledResolver;
use OSW3\Api\Resolver\CollectionDeprecationStartAtResolver;
use OSW3\Api\Resolver\CollectionPaginationMaxLimitResolver;
use OSW3\Api\Resolver\CollectionDeprecationSunsetAtResolver;
use OSW3\Api\Resolver\EndpointRateLimitByApplicationResolver;
use OSW3\Api\Resolver\EndpointRateLimitIncludeHeadersResolver;
use OSW3\Api\Resolver\CollectionRateLimitByApplicationResolver;
use OSW3\Api\Resolver\CollectionRateLimitIncludeHeadersResolver;
use OSW3\Api\Resolver\EndpointPaginationAllowLimitOverrideResolver;
use OSW3\Api\Resolver\CollectionPaginationAllowLimitOverrideResolver;

return static function($definition): void
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
            // Deprecation
            // ──────────────────────────────
			->arrayNode('deprecation')
                ->info('API deprecation notices')
                ->addDefaultsIfNotSet()->children()

                    ->booleanNode('enabled')
                        ->info('Enable or disable the deprecation for this API provider.')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()

                    ->scalarNode('start_at')
                        ->info('Deprecation since date')
                        ->defaultNull()
                    ->end()

                    ->scalarNode('sunset_at')
                        ->info('Deprecation removal date')
                        ->defaultNull()
                    ->end()

                    ->scalarNode('link')
                        ->info('Deprecation link')
                        ->defaultNull()
                    ->end()

                    ->scalarNode('successor')
                        ->info('Deprecation successor link')
                        ->defaultNull()
                    ->end()

                    ->scalarNode('message')
                        ->info('Deprecation message')
                        ->defaultNull()
                    ->end()

                ->end()
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

                    ->booleanNode('beta')
                        ->info('Indicates whether this API version is in beta. If true, clients should be aware that the API may change.')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()

                    ->scalarNode('directive')
                        ->info('Defines the HTTP header used for API versioning.')
                        ->defaultValue("Accept")
                        ->treatNullLike('Accept')
                    ->end()

                    ->scalarNode('pattern')
                        ->info('Defines the pattern used for API versioning via HTTP headers. Placeholders {vendor} and {version} will be replaced dynamically.')
                        ->defaultValue("application/vnd.{vendor}.{version}+json")
                        ->treatNullLike('application/vnd.{vendor}.{version}+json')
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
                        ->defaultValue('api_{version}_{collection}_{action}')
                        ->treatNullLike('api_{version}_{collection}_{action}')
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

                    ->arrayNode('parameters')
                        ->info('Query parameter names for pagination.')
                        ->addDefaultsIfNotSet()->children()

                            ->scalarNode('page')
                                ->info('Parameter name for the page number.')
                                ->defaultValue('page')
                                ->treatNullLike('page')
                            ->end()

                            ->scalarNode('limit')
                                ->info('Parameter name for the number of items per page.')
                                ->defaultValue('limit')
                                ->treatNullLike('limit')
                            ->end()

                        ->end()
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
                        ->treatNullLike('url')
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
                        ->treatNullLike(false)
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

                    ->scalarNode('single')
                        ->info('Path to the response template file used as a model for formatting the API output for single items.')
                        ->defaultValue('Resources/templates/single.yaml')
                        ->treatNullLike('Resources/templates/single.yaml')
                    ->end()

                    ->scalarNode('delete')
                        ->info('Path to the response template file used as a model for formatting the API output for delete operations.')
                        ->defaultValue('Resources/templates/delete.yaml')
                        ->treatNullLike('Resources/templates/delete.yaml')
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

                    ->arrayNode('format')
                        ->info('Response format settings.')
                        ->addDefaultsIfNotSet()->children()

                            ->enumNode('type')
                                ->info('Type of the response format.')
                                // ->values(['json', 'xml', 'yaml', 'csv', 'toon'])
                                ->values(array_keys(MimeType::toArray(true)))
                                ->defaultValue('json')
                                ->treatNullLike('json')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(fn($v) => strtolower($v))
                                ->end()
                            ->end()

                            ->scalarNode('mime_type')
                                ->info('Override the MIME type of the response format.')
                                ->defaultNull()
                            ->end()

                            ->booleanNode('override')
                                ->info('If true, allows clients to override the response format using a URL parameter (e.g. ?format=xml).')
                                ->defaultFalse()
                                ->treatNullLike(false)
                            ->end()

                            ->scalarNode('parameter')
                                ->info('Name of the URL parameter used to override the response format.')
                                ->defaultValue('_format')
                                ->treatNullLike('_format')
                            ->end()

                        ->end()
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
                                ->values(['sha1', 'sha256', 'sha512'])
                                ->defaultValue('sha256')
                                ->treatNullLike('sha256')
                            ->end()

                        ->end()
                    ->end()

                    ->arrayNode('cache_control')
                        ->info('List of Cache-Control directives to include in responses.')
                        ->addDefaultsIfNotSet()->children()

                            ->booleanNode('enabled')
                                ->info('If true, enables Cache-Control headers.')
                                ->defaultFalse()
                                ->treatNullLike(false)
                            ->end()

                            ->booleanNode('public')
                                ->info('If true, sets Cache-Control to "public", allowing shared caches. If false, sets to "private".')
                                ->defaultTrue()
                                ->treatNullLike(true)
                            ->end()

                            ->booleanNode('no_store')
                                ->info('If true, adds "no-store" to Cache-Control.')
                                ->defaultFalse()
                                ->treatNullLike(false)
                            ->end()

                            ->booleanNode('must_revalidate')
                                ->info('If true, adds "must-revalidate" to Cache-Control.')
                                ->defaultTrue()
                                ->treatNullLike(true)
                            ->end()

                            ->integerNode('max_age')
                                ->info('Max age in seconds (0 = no cache).')
                                ->defaultValue(3600)
                                ->treatNullLike(3600)
                                ->min(0)
                                ->max(31536000)
                            ->end()

                        ->end()
                    ->end()

                    ->arrayNode('headers')
                        ->info('HTTP headers to include in API responses.')
                        ->addDefaultsIfNotSet()->children()

                            ->booleanNode('strip_x_prefix')
                                ->info('If true, strips "X-" prefix from headers when exposing them.')
                                ->defaultTrue()
                                ->treatNullLike(true)
                            ->end()

                            ->booleanNode('keep_legacy')
                                ->info('If true, keeps "X-" prefix in headers when exposing them.')
                                ->defaultTrue()
                                ->treatNullLike(true)
                            ->end()

                            ->arrayNode('exposed')
                                ->info('List of headers to expose in CORS requests.')
                                ->scalarPrototype()->end()
                                ->defaultValue([])
                            ->end()
                            
                            ->arrayNode('cors')
                                ->info('CORS configuration for the API.')
                                ->addDefaultsIfNotSet()->children()

                                    ->arrayNode('origins')
                                        ->info('List of allowed origins for CORS requests.')
                                        ->scalarPrototype()->end()
                                        ->defaultValue(['*'])
                                        ->treatNullLike(['*'])
                                    ->end()

                                    ->arrayNode('methods')
                                        ->info('List of allowed HTTP methods for CORS requests.')
                                        ->scalarPrototype()->end()
                                        ->defaultValue(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'])
                                        ->treatNullLike(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'])
                                    ->end()

                                    ->arrayNode('attributes')
                                        ->info('List of headers to expose in CORS requests.')
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()

                                    ->booleanNode('credentials')
                                        ->info('If true, includes credentials in CORS requests.')
                                        ->defaultFalse()
                                        ->treatNullLike(false)
                                    ->end()

                                ->end()
                            ->end()

                            ->arrayNode('vary')
                                ->info('List of headers to include in the Vary response header.')
                                ->scalarPrototype()->end()
                                ->defaultValue(['Origin', 'Accept', 'Accept-Language', 'Accept-Encoding', 'Accept', 'Authorization', 'API-Version'])
                                ->treatNullLike(['Origin', 'Accept', 'Accept-Language', 'Accept-Encoding', 'Accept', 'Authorization', 'API-Version'])
                            ->end()

                            ->arrayNode('custom')
                                ->info('Custom headers to always include in responses. Key is header name, value is header value.')
                                ->normalizeKeys(false)
                                ->scalarPrototype()->end()
                                ->defaultValue([])
                            ->end() 

                            ->arrayNode('remove')
                                ->info('List of headers to remove from responses.')
                                ->scalarPrototype()->end()
                                ->defaultValue([])
                            ->end()

                        ->end()
                    ->end()

                    ->arrayNode('compression')
                        ->info('Configuration for response compression settings.')
                        ->addDefaultsIfNotSet()->children()

                            ->booleanNode('enabled')
                                ->info('Enable or disable response compression.')
                                ->defaultFalse()
                                ->treatNullLike(false)
                            ->end()

                            ->enumNode('format')
                                ->info('Compression format to use.')
                                ->defaultValue('gzip')
                                ->values(['gzip', 'deflate', 'brotli'])
                                ->treatNullLike('gzip')
                            ->end()

                            ->integerNode('level')
                                ->info('Compression level (0-9) for the selected format.')
                                ->defaultValue(6)
                                ->treatNullLike(6)
                                ->min(0)
                                ->max(9)
                            ->end()

                        ->end()
                    ->end()

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
                        ->defaultValue(['password', 'secret'])
                        ->treatNullLike(['password', 'secret'])
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
                        ->treatNullLike(false)
                    ->end()

                ->end()
            ->end()

            // ──────────────────────────────
            // HOOKS
            // ──────────────────────────────
            ->arrayNode('hooks')
                ->info('Defines callable hooks to be executed before and after all actions.')
                ->addDefaultsIfNotSet()->children()

                    ->enumNode('merge')
                        ->info('Defines how to handle merging hooks: "replace" to overwrite existing hooks, "append" to add to them, or "prepend" to add them at the beginning.')
                        ->values(['replace', 'append', 'prepend'])
                        ->defaultValue('append')
                        ->treatNullLike('append')
                    ->end()

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

                    ->arrayNode('around')
                        ->info('List of callable listeners to execute **around** the endpoint action.')
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()

                    ->arrayNode('on_success')
                        ->info('List of callable listeners to execute on success action.')
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()

                    ->arrayNode('on_failure')
                        ->info('List of callable listeners to execute on failure action.')
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()

                    ->arrayNode('on_complete')
                        ->info('List of callable listeners to execute on complete action.')
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
            // Access control
            // ──────────────────────────────
            ->arrayNode('access_control')
                ->info('Defines access control settings for the API provider.')
                ->addDefaultsIfNotSet()->children()

                    ->enumNode('merge')
                        ->info('Defines how to handle merging access control settings: "replace" to overwrite existing settings, "append" to add to them, or "prepend" to add them at the beginning.')
                        ->values(['replace', 'append', 'prepend'])
                        ->defaultValue('append')
                        ->treatNullLike('append')
                    ->end()

                    ->arrayNode('roles')
                        ->info('List of Symfony security roles required to access this API provider.')
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()

                    ->scalarNode('voter')
                        ->info('Optional custom voter FQCN. If set, Symfony will use this voter to determine access instead of roles or expressions.')
                        ->defaultNull()
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

                            ->scalarNode('identifier')
                                ->info('Identifier field for the User entity (e.g., "email").')
                                ->defaultValue('email')
                                ->treatNullLike('email')
                            ->end()

                            ->scalarNode('password')
                                ->info('Password field for the User entity (e.g., "password").')
                                ->defaultValue('password')
                                ->treatNullLike('password')
                            ->end()

                        ->end()
                    ->end()

                    ->scalarNode('group')
                        ->info('Route group for security-related endpoints (login, registration, etc.).')
                        ->defaultValue('security')
                        ->treatNullLike('security')
                    ->end()

                    ->arrayNode('registration')
                        ->info('Defines the registration settings for the API.')
                        ->addDefaultsIfNotSet()->children()

                            ->arrayNode('register')
                                ->info('Defines the registration endpoint settings.')
                                ->addDefaultsIfNotSet()->children()
 
                                    ->booleanNode('enabled')
                                        ->info('Enable or disable registration.')
                                        ->defaultFalse()
                                        ->treatNullLike(false)
                                    ->end()

                                    ->scalarNode('path')
                                        ->info('Path for the registration endpoint.')
                                        ->defaultNull('/api/{version}/register')
                                        ->treatNullLike('/api/{version}/register')    
                                    ->end()

                                    ->arrayNode('hosts')
                                        ->info('Configure specific hosts for the register endpoint routes.')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()

                                    ->arrayNode('schemes')
                                        ->info('Configure specific schemes (http, https) for the register endpoint routes.')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()

                                    ->scalarNode('controller')
                                        ->info('Optional Symfony controller (FQCN::method) to handle registration. If not defined, the default RegistrationController will be used.')
                                        ->defaultValue('OSW3\Api\Controller\RegistrationController::register')
                                        ->treatNullLike('OSW3\Api\Controller\RegistrationController::register')
                                        ->validate()
                                            ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                            ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.', 'register.controller')
                                        ->end()
                                    ->end()

                                    // ->arrayNode('fields')
                                    //     ->info('Registration fields mapping.')
                                    //     ->normalizeKeys(false)
                                    //     ->scalarPrototype()->end()
                                    //     ->defaultValue([
                                    //         'username' => 'email',
                                    //         'password' => 'password',
                                    //         'confirm'  => 'confirmPassword',
                                    //     ])
                                    // ->end()

                                    ->arrayNode('fields')
                                        ->info('Registration fields mapping.')
                                        ->addDefaultsIfNotSet()->children()
                                        
                                            ->scalarNode('username')
                                                ->info('')
                                                ->defaultValue('email')
                                                ->treatNullLike('email')
                                            ->end()
                                        
                                            ->scalarNode('password')
                                                ->info('')
                                                ->defaultValue('password')
                                                ->treatNullLike('password')
                                            ->end()

                                        ->end()
                                    ->end()
                                
                                ->end()
                            ->end()

                            ->arrayNode('verify_email')
                                ->info('Defines the email verification settings for the API.')
                                ->addDefaultsIfNotSet()->children()
 
                                    ->booleanNode('enabled')
                                        ->info('Enable or disable email verification.')
                                        ->defaultFalse()
                                        ->treatNullLike(false)
                                    ->end()

                                    ->scalarNode('path')
                                        ->info('Path for the email verification endpoint.')
                                        ->defaultNull('/api/{version}/verify-email')
                                        ->treatNullLike('/api/{version}/verify-email')
                                    ->end()

                                    ->arrayNode('hosts')
                                        ->info('Configure specific hosts for the email verification endpoint routes.')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()

                                    ->arrayNode('schemes')
                                        ->info('Configure specific schemes (http, https) for the email verification endpoint routes.')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()

                                    ->scalarNode('controller')
                                        ->info('Optional Symfony controller (FQCN::method) to handle email verification. If not defined, the default RegistrationController will be used.')
                                        ->defaultValue('OSW3\Api\Controller\RegistrationController::verifyEmail')
                                        ->treatNullLike('OSW3\Api\Controller\RegistrationController::verifyEmail')
                                        ->validate()
                                            ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                            ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.', 'verify_email.controller')
                                        ->end()
                                    ->end()
                                
                                ->end()
                            ->end()

                            ->arrayNode('resend_verification')
                                ->info('Defines the resend email verification settings for the API.')
                                ->addDefaultsIfNotSet()->children()
 
                                    ->booleanNode('enabled')
                                        ->info('Enable or disable resend email verification.')
                                        ->defaultFalse()
                                        ->treatNullLike(false)
                                    ->end()

                                    ->scalarNode('path')
                                        ->info('Path for the resend email verification endpoint.')
                                        ->defaultNull('/api/{version}/resend-verification')
                                        ->treatNullLike('/api/{version}/resend-verification')
                                    ->end()

                                    ->arrayNode('hosts')
                                        ->info('Configure specific hosts for the resend email verification endpoint routes.')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()

                                    ->arrayNode('schemes')
                                        ->info('Configure specific schemes (http, https) for the resend email verification endpoint routes.')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()

                                    ->scalarNode('controller')
                                        ->info('Optional Symfony controller (FQCN::method) to handle registration. If not defined, the default RegistrationController will be used.')
                                        ->defaultValue('OSW3\Api\Controller\RegistrationController::resendVerification')
                                        ->treatNullLike('OSW3\Api\Controller\RegistrationController::resendVerification')
                                        ->validate()
                                            ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                            ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.', 'resend_verification.controller')
                                        ->end()
                                    ->end()

                                ->end()
                            ->end()

                        ->end()
                    ->end()

                    ->arrayNode('authentication')
                        ->info('Defines the authentication settings for the API.')
                        ->addDefaultsIfNotSet()->children()

                            ->arrayNode('login')
                                ->info('Defines the login settings for the API.')
                                ->addDefaultsIfNotSet()->children()
 
                                    ->booleanNode('enabled')
                                        ->info('Enable or disable login.')
                                        ->defaultFalse()
                                        ->treatNullLike(false)
                                    ->end()

                                    ->scalarNode('path')
                                        ->info('Path for the login endpoint.')
                                        ->defaultNull('/api/{version}/login')
                                        ->treatNullLike('/api/{version}/login')
                                    ->end()

                                    ->arrayNode('hosts')
                                        ->info('Configure specific hosts for the login endpoint routes.')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()

                                    ->arrayNode('schemes')
                                        ->info('Configure specific schemes (http, https) for the login endpoint routes.')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()

                                    ->scalarNode('controller')
                                        ->info('Optional Symfony controller (FQCN::method) to handle login. If not defined, the default AuthenticationController will be used.')
                                        ->defaultValue('OSW3\Api\Controller\AuthenticationController::login')
                                        ->treatNullLike('OSW3\Api\Controller\AuthenticationController::login')
                                        ->validate()
                                            ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                            ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.', 'login.controller')
                                        ->end()
                                    ->end()

                                    ->arrayNode('fields')
                                        ->info('Login fields mapping.')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([
                                            'username' => 'email',
                                            'password' => 'password',
                                        ])
                                    ->end()
                                
                                ->end()
                            ->end()

                            ->arrayNode('logout')
                                ->info('Defines the logout settings for the API.')
                                ->addDefaultsIfNotSet()->children()
 
                                    ->booleanNode('enabled')
                                        ->info('Enable or disable logout.')
                                        ->defaultFalse()
                                        ->treatNullLike(false)
                                    ->end()

                                    ->scalarNode('path')
                                        ->info('Path for the logout endpoint.')
                                        ->defaultNull('/api/{version}/logout')
                                        ->treatNullLike('/api/{version}/logout')
                                    ->end()

                                    ->arrayNode('hosts')
                                        ->info('Configure specific hosts for the logout endpoint routes.')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()

                                    ->arrayNode('schemes')
                                        ->info('Configure specific schemes (http, https) for the logout endpoint routes.')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()

                                    ->scalarNode('controller')
                                        ->info('Optional Symfony controller (FQCN::method) to handle logout. If not defined, the default AuthenticationController will be used.')
                                        ->defaultValue('OSW3\Api\Controller\AuthenticationController::logout')
                                        ->treatNullLike('OSW3\Api\Controller\AuthenticationController::logout')
                                        ->validate()
                                            ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                            ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.', 'logout.controller')
                                        ->end()
                                    ->end()

                                ->end()
                            ->end()

                            ->arrayNode('refresh_token')
                                ->info('Defines the refresh token settings for the API.')
                                ->addDefaultsIfNotSet()->children()
 
                                    ->booleanNode('enabled')
                                        ->info('Enable or disable refresh token.')
                                        ->defaultFalse()
                                        ->treatNullLike(false)
                                    ->end()

                                    ->scalarNode('path')
                                        ->info('Path for the refresh token endpoint.')
                                        ->defaultNull('/api/{version}/refresh-token')
                                        ->treatNullLike('/api/{version}/refresh-token')
                                    ->end()

                                    ->arrayNode('hosts')
                                        ->info('Configure specific hosts for the refresh token endpoint routes.')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()

                                    ->arrayNode('schemes')
                                        ->info('Configure specific schemes (http, https) for the refresh token endpoint routes.')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()

                                    ->scalarNode('controller')
                                        ->info('Optional Symfony controller (FQCN::method) to handle refresh token. If not defined, the default AuthenticationController will be used.')
                                        ->defaultValue('OSW3\Api\Controller\AuthenticationController::refreshToken')
                                        ->treatNullLike('OSW3\Api\Controller\AuthenticationController::refreshToken')
                                        ->validate()
                                            ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                            ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.', 'refresh_token.controller')
                                        ->end()
                                    ->end()

                                ->end()
                            ->end()

                        ->end()
                    ->end()

                    ->arrayNode('password')
                        ->info('Defines the password settings for the API.')
                        ->addDefaultsIfNotSet()->children()

                            ->arrayNode('reset_request')
                                ->info('Defines the password reset request settings for the API.')
                                ->addDefaultsIfNotSet()->children()
 
                                    ->booleanNode('enabled')
                                        ->info('Enable or disable password reset request.')
                                        ->defaultFalse()
                                        ->treatNullLike(false)
                                    ->end()

                                    ->scalarNode('path')
                                        ->info('Path for the password reset request endpoint.')
                                        ->defaultNull('/api/{version}/password/reset')
                                        ->treatNullLike('/api/{version}/password/reset')
                                    ->end()

                                    ->arrayNode('hosts')
                                        ->info('Configure specific hosts for the password reset request endpoint routes.')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()

                                    ->arrayNode('schemes')
                                        ->info('Configure specific schemes (http, https) for the password reset request endpoint routes.')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()

                                    ->scalarNode('controller')
                                        ->info('Optional Symfony controller (FQCN::method) to handle registration. If not defined, the default PasswordController will be used.')
                                        ->defaultValue('OSW3\Api\Controller\PasswordController::resetRequest')
                                        ->treatNullLike('OSW3\Api\Controller\PasswordController::resetRequest')
                                        ->validate()
                                            ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                            ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.', 'reset_request.controller')
                                        ->end()
                                    ->end()
                                
                                ->end()
                            ->end()

                            ->arrayNode('reset_confirm')
                                ->info('Defines the password reset confirmation settings for the API.')
                                ->addDefaultsIfNotSet()->children()
 
                                    ->booleanNode('enabled')
                                        ->info('Enable or disable password reset confirmation.')
                                        ->defaultFalse()
                                        ->treatNullLike(false)
                                    ->end()

                                    ->scalarNode('path')
                                        ->info('Path for the password reset confirmation endpoint.')
                                        ->defaultNull('/api/{version}/password/reset/confirm')
                                        ->treatNullLike('/api/{version}/password/reset/confirm')
                                    ->end()

                                    ->arrayNode('hosts')
                                        ->info('Configure specific hosts for the password reset confirmation endpoint routes.')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()

                                    ->arrayNode('schemes')
                                        ->info('Configure specific schemes (http, https) for the password reset confirmation endpoint routes.')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()

                                    ->scalarNode('controller')
                                        ->info('Optional Symfony controller (FQCN::method) to handle registration. If not defined, the default PasswordController will be used.')
                                        ->defaultValue('OSW3\Api\Controller\PasswordController::resetConfirm')
                                        ->treatNullLike('OSW3\Api\Controller\PasswordController::resetConfirm')
                                        ->validate()
                                            ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                            ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.', 'reset_confirm.controller')
                                        ->end()
                                    ->end()

                                ->end()
                            ->end()

                            ->arrayNode('change')
                                ->info('Defines the password change settings for the API.')
                                ->addDefaultsIfNotSet()->children()
 
                                    ->booleanNode('enabled')
                                        ->info('Enable or disable password change.')
                                        ->defaultFalse()
                                        ->treatNullLike(false)
                                    ->end()

                                    ->scalarNode('path')
                                        ->info('Path for the password change endpoint.')
                                        ->defaultNull('/api/{version}/password/change')
                                        ->treatNullLike('/api/{version}/password/change')
                                    ->end()

                                    ->arrayNode('hosts')
                                        ->info('Configure specific hosts for the password change endpoint routes.')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()

                                    ->arrayNode('schemes')
                                        ->info('Configure specific schemes (http, https) for the password change endpoint routes.')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()

                                    ->scalarNode('controller')
                                        ->info('Optional Symfony controller (FQCN::method) to handle registration. If not defined, the default PasswordController will be used.')
                                        ->defaultValue('OSW3\Api\Controller\PasswordController::change')
                                        ->treatNullLike('OSW3\Api\Controller\PasswordController::change')
                                        ->validate()
                                            ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                            ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.', 'change.controller')
                                        ->end()
                                    ->end()

                                ->end()
                            ->end()

                        ->end()
                    ->end()

                ->end()
            ->end()

            // ──────────────────────────────
            // Debug
            // ──────────────────────────────
			->arrayNode('debug')
                ->info('Debug configuration')
                ->addDefaultsIfNotSet()->children()

                    ->booleanNode('enabled')
                        ->info('Enable or disable debug.')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()

			    ->end()
            ->end()

            // ──────────────────────────────
            // Collections (Doctrine Entities)
            // ──────────────────────────────
			->arrayNode('collections')
                ->info('List of Doctrine entity classes to expose as REST collections.')
                ->useAttributeAsKey('entity')
                ->requiresAtLeastOneElement()
                ->arrayPrototype()
                // ->ignoreExtraKeys(false)
                    ->children()

                        // ──────────────────────────────
                        // Enabled
                        // ──────────────────────────────
                        ->booleanNode('enabled')
                            ->info('Enable or disable this provider.')
                            ->defaultNull()
                        ->end()

                        // ──────────────────────────────
                        // Deprecation
                        // ──────────────────────────────
                        ->arrayNode('deprecation')
                            ->info('API deprecation notices')
                            ->addDefaultsIfNotSet()->children()

                                ->booleanNode('enabled')
                                    ->info('Enable or disable the deprecation for this collection.')
                                    ->defaultNull()
                                    ->treatNullLike(false)
                                ->end()

                                ->scalarNode('start_at')
                                    ->info('Deprecation since date')
                                    ->defaultNull()
                                ->end()

                                ->scalarNode('sunset_at')
                                    ->info('Deprecation sunset date')
                                    ->defaultNull()
                                ->end()

                                ->scalarNode('link')
                                    ->info('Deprecation link')
                                    ->defaultNull()
                                ->end()

                                ->scalarNode('successor')
                                    ->info('Deprecation successor link')
                                    ->defaultNull()
                                ->end()

                                ->scalarNode('message')
                                    ->info('Deprecation message')
                                    ->defaultNull()
                                ->end()

                            ->end()
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
                        // Pagination
                        // ──────────────────────────────
                        ->arrayNode('pagination')
                            ->info('Default pagination behavior for a specific collection.')
                            ->addDefaultsIfNotSet()->children()
                            
                                ->variableNode('enabled')
                                    ->info('Enable or disable pagination. If null, inherits from parent.')
                                    ->validate()
                                        ->ifTrue(fn($v) => !in_array($v, [true, false, null], true))
                                        ->thenInvalid('The "enabled" value must be true, false, or null.')
                                    ->end()
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

                                ->variableNode('enabled')
                                    ->info('Enable or disable rate limiting for this API provider.')
                                    ->validate()
                                        ->ifTrue(fn($v) => !in_array($v, [true, false, null], true))
                                        ->thenInvalid('The "enabled" value must be true, false, or null.')
                                    ->end()
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

                                ->scalarNode('single')
                                    ->info('Path to the response template file used as a model for formatting the API output for single items.')
                                    ->defaultNull()
                                ->end()

                                ->scalarNode('delete')
                                    ->info('Path to the response template file used as a model for formatting the API output for delete operations.')
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
                        // Serialization
                        // ──────────────────────────────
                        ->arrayNode('serialization')
                            ->info('Defines serialization settings applied to API responses, including ignored attributes, date formatting, and null value handling.')
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
                        // HOOKS
                        // ──────────────────────────────
                        ->arrayNode('hooks')
                            ->info('Defines callable hooks to be executed at various points in the request lifecycle.')
                            ->addDefaultsIfNotSet()->children()

                                ->enumNode('merge')
                                    ->info('Defines how to handle merging hooks: "replace" to overwrite existing hooks, "append" to add to them, or "prepend" to add them at the beginning.')
                                    ->values(['replace', 'append', 'prepend'])
                                    ->treatNullLike('append')
                                ->end()

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

                                ->arrayNode('around')
                                    ->info('List of callable listeners to execute **around** the endpoint action.')
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()

                                ->arrayNode('on_success')
                                    ->info('List of callable listeners to execute on success action.')
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()

                                ->arrayNode('on_failure')
                                    ->info('List of callable listeners to execute on failure action.')
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()

                                ->arrayNode('on_complete')
                                    ->info('List of callable listeners to execute on complete action.')
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
                        // Access control
                        // ──────────────────────────────
                        ->arrayNode('access_control')
                            ->info('Defines access control settings for this collection.')
                            ->addDefaultsIfNotSet()->children()

                                ->enumNode('merge')
                                    ->info('Defines how to handle merging access control settings: "replace" to overwrite existing settings, "append" to add to them, or "prepend" to add them at the beginning.')
                                    ->values(['replace', 'append', 'prepend'])
                                    ->defaultValue('append')
                                    ->treatNullLike('append')
                                ->end()

                                ->arrayNode('roles')
                                    ->info('List of Symfony security roles required to access this collection.')
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()

                                ->scalarNode('voter')
                                    ->info('Optional custom voter FQCN. If set, Symfony will use this voter to determine access instead of roles or expressions.')
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
                                    // Deprecation
                                    // ──────────────────────────────
                                    ->arrayNode('deprecation')
                                        ->info('API deprecation notices')
                                        ->addDefaultsIfNotSet()->children()

                                            ->booleanNode('enabled')
                                                ->info('Enable or disable the deprecation for this endpoint.')
                                                ->defaultNull()
                                                ->treatNullLike(false)
                                            ->end()

                                            ->scalarNode('start_at')
                                                ->info('Deprecation since date')
                                                ->defaultNull()
                                            ->end()

                                            ->scalarNode('sunset_at')
                                                ->info('Deprecation sunset date')
                                                ->defaultNull()
                                            ->end()

                                            ->scalarNode('link')
                                                ->info('Deprecation link')
                                                ->defaultNull()
                                            ->end()

                                            ->scalarNode('successor')
                                                ->info('Deprecation successor link')
                                                ->defaultNull()
                                            ->end()

                                            ->scalarNode('message')
                                                ->info('Deprecation message')
                                                ->defaultNull()
                                            ->end()

                                        ->end()
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
                            
                                            ->variableNode('enabled')
                                                ->info('Enable or disable pagination. If null, inherits from parent.')
                                                ->validate()
                                                    ->ifTrue(fn($v) => !in_array($v, [true, false, null], true))
                                                    ->thenInvalid('The "enabled" value must be true, false, or null.')
                                                ->end()
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

                                            ->variableNode('enabled')
                                                ->info('Enable or disable rate limiting for this API provider.')
                                                ->validate()
                                                    ->ifTrue(fn($v) => !in_array($v, [true, false, null], true))
                                                    ->thenInvalid('The "enabled" value must be true, false, or null.')
                                                ->end()
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

                                            ->scalarNode('single')
                                                ->info('Path to the response template file used as a model for formatting the API output for single items.')
                                                ->defaultNull()
                                            ->end()

                                            ->scalarNode('template')
                                                ->info('Path to the response template file used as a model for formatting the API output for generic templates.')
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
                                    // Repository config
                                    // ──────────────────────────────
                                    ->arrayNode('repository')
                                        ->info('Configuration options for the repository used to retrieve data for this endpoint.')
                                        ->addDefaultsIfNotSet()->children()

                                            ->scalarNode('class')
                                                ->info('Optional: the fully qualified class name of a custom repository. Defaults to the default Doctrine repository for the entity.')
                                                ->defaultNull()

                                                // TODO: Validator to check if class exists and is a valid repository
                                                // ->validate()
                                                //     ->ifTrue(fn($hooks) => !HooksValidator::validate($hooks))
                                                //     ->thenInvalid('One or more hooks (before/after) are invalid. They must be valid callables (Class::method or callable).')
                                                // ->end()
                                            ->end()

                                            ->scalarNode('method')
                                                ->info(<<<'INFO'
                                                    Repository method to call. This can be either:
                                                    - A standard Doctrine repository method: `find`, `findAll`, `findBy`, `findOneBy`, `count`.
                                                    - A custom public method defined in your repository class.
                                                    The method will be called when the controller is null.
                                                    INFO)
                                                ->defaultNull()
                                            ->end()

                                            ->arrayNode('parameters')
                                                ->info('Parameters to pass to the repository method. Keys are parameter names, values are the values to pass.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                            ->end()

                                            ->arrayNode('criteria')
                                                ->info('Criteria for filtering data. Keys are field names, values are the values to filter by.')
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
                                                ->treatNullLike(0)
                                                ->min(0)
                                            ->end()

                                            ->enumNode('fetch_mode')
                                                ->info('Optional fetch mode for Doctrine relations. "lazy" loads relations on demand, "eager" loads them immediately.')
                                                ->values(['lazy', 'eager'])
                                                ->defaultValue('lazy')
                                                ->treatNullLike('lazy')
                                            ->end()

                                        ->end()
                                    ->end()

                                    // ──────────────────────────────
                                    // Metadata config
                                    // ──────────────────────────────
                                    ->arrayNode('metadata')
                                        ->info('Free-form metadata for documentation and templating.')
                                        ->normalizeKeys(false)
                                        ->useAttributeAsKey('name')
                                        ->variablePrototype()->end()
                                    ->end()

                                    // ──────────────────────────────
                                    // Access control
                                    // ──────────────────────────────
                                    ->arrayNode('access_control')
                                        ->info('Defines the access control rules for this endpoint.')
                                        ->addDefaultsIfNotSet()->children()

                                            ->enumNode('merge')
                                                ->info('Defines how to handle merging access control settings: "replace" to overwrite existing settings, "append" to add to them, or "prepend" to add them at the beginning.')
                                                ->values(['replace', 'append', 'prepend'])
                                                ->defaultValue('append')
                                                ->treatNullLike('append')
                                            ->end()

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
                                        ->info('Defines callable hooks to be executed at various points in the request lifecycle.')
                                        ->addDefaultsIfNotSet()->children()

                                            ->enumNode('merge')
                                                ->info('Defines how to handle merging hooks: "replace" to overwrite existing hooks, "append" to add to them, or "prepend" to add them at the beginning.')
                                                ->values(['replace', 'append', 'prepend'])
                                                ->treatNullLike('append')
                                            ->end()

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

                                            ->arrayNode('around')
                                                ->info('List of callable listeners to execute **around** the endpoint action.')
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            ->arrayNode('on_success')
                                                ->info('List of callable listeners to execute on success action.')
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            ->arrayNode('on_failure')
                                                ->info('List of callable listeners to execute on failure action.')
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            ->arrayNode('on_complete')
                                                ->info('List of callable listeners to execute on complete action.')
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                        ->end()

                                        ->validate()
                                            ->ifTrue(fn($hooks) => !HooksValidator::validate($hooks))
                                            ->thenInvalid('One or more hooks (before/after) are invalid. They must be valid callables (Class::method or callable).')
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

            // Deprecation
            ProviderDeprecationStartAtResolver::resolve($providers);
            ProviderDeprecationSunsetAtResolver::resolve($providers);


            // ──────────────────────────────
            // Collections (Doctrine Entities)
            // ──────────────────────────────

            // Enabled
            CollectionIsEnabledResolver::default($providers);

            // Deprecation
            CollectionDeprecationEnabledResolver::default($providers);
            CollectionDeprecationStartAtResolver::default($providers);
            CollectionDeprecationStartAtResolver::resolve($providers);
            CollectionDeprecationSunsetAtResolver::default($providers);
            CollectionDeprecationSunsetAtResolver::resolve($providers);

            // Name
            CollectionNameResolver::resolve($providers);

            // Route
            CollectionRoutePatternResolver::default($providers);
            CollectionRoutePrefixResolver::default($providers);
            CollectionRoutePrefixResolver::resolve($providers);

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

            // Deprecation
            EndpointDeprecationEnabledResolver::default($providers);
            EndpointDeprecationStartAtResolver::default($providers);
            EndpointDeprecationStartAtResolver::resolve($providers);
            EndpointDeprecationSunsetAtResolver::default($providers);
            EndpointDeprecationSunsetAtResolver::resolve($providers);

            // Route
            EndpointRouteNameResolver::default($providers);
            EndpointRouteNameResolver::resolve($providers);
            EndpointRoutePathResolver::default($providers);
            EndpointRoutePathResolver::resolve($providers);
            EndpointRouteControllerResolver::resolve($providers);
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

            // Repository


            return $providers;
        })
    ->end(); // of Version generator
 };