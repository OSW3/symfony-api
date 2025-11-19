<?php

use OSW3\Api\Enum\MimeType;
use OSW3\Api\Validator\EntityValidator;
use OSW3\Api\Validator\ControllerValidator;
use OSW3\Api\Validator\TransformerValidator;
use OSW3\Api\Resolver\CollectionNameResolver;
use Symfony\Component\HttpFoundation\Request;
use OSW3\Api\Resolver\ApiVersionNumberResolver;
use OSW3\Api\Resolver\EndpointRouteNameResolver;
use OSW3\Api\Resolver\EndpointRoutePathResolver;
use OSW3\Api\Resolver\EndpointTemplatesResolver;
use OSW3\Api\Resolver\EndpointUrlSupportResolver;
use OSW3\Api\Resolver\CollectionIsEnabledResolver;
use OSW3\Api\Resolver\CollectionTemplatesResolver;
use OSW3\Api\Resolver\EndpointUrlAbsoluteResolver;
use OSW3\Api\Resolver\EndpointUrlPropertyResolver;
use OSW3\Api\Resolver\ProviderRoutePrefixResolver;
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
use OSW3\Api\Resolver\ProviderAuthenticationNameResolver;
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
use OSW3\Api\Resolver\ProviderAuthenticationRoutePrefixResolver;
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
                        ->defaultValue('api')
                        ->treatNullLike('api')
                    ->end()

                    ->arrayNode('hosts')
                        ->info('List of hostnames for this API provider (e.g. api.example.com).')
                        ->normalizeKeys(false)
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()

                    ->arrayNode('schemes')
                        ->info('List of schemes for this API provider (e.g. https, http).')
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

                    ->scalarNode('account')
                        ->info('Path to the response template file used as a model for formatting the API output for account operations.')
                        ->defaultValue('Resources/templates/account.yaml')
                        ->treatNullLike('Resources/templates/account.yaml')
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
            // Authentication
            // ──────────────────────────────
			->arrayNode('authentication')
                ->info('Defines authentication settings for the API.')
                ->arrayPrototype()
                ->ignoreExtraKeys(false)
                    ->children()

                        // ──────────────────────────────
                        // Enabled
                        // ──────────────────────────────
                        ->booleanNode('enabled')
                            ->info('Enable or disable this authentication provider.')
                            ->defaultNull()
                        ->end()

                        // ──────────────────────────────
                        // Deprecation
                        // ──────────────────────────────
                        ->arrayNode('deprecation')
                            ->info('API deprecation notices for this authentication provider.')
                            ->addDefaultsIfNotSet()->children()

                                ->booleanNode('enabled')
                                    ->info('Enable or disable the deprecation for this authentication provider.')
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
                            ->info('Name / Alias of the entity')
                            ->defaultNull()
                            ->treatNullLike(null)
                        ->end()

                        // ──────────────────────────────
                        // Route
                        // ──────────────────────────────
                        ->arrayNode('route')
                            ->info('Override default route name or URL prefix for security-related endpoints.')
                            ->addDefaultsIfNotSet()->children()

                                ->scalarNode('pattern')
                                    ->info('Custom route name pattern. Falls back to global `routes.name` if null.')
                                    ->defaultNull()
                                ->end()

                                ->scalarNode('prefix')
                                    ->info('Route prefix for security-related endpoints (login, registration, etc.).')
                                    ->defaultNull()
                                ->end()

                                ->scalarNode('additional_prefix')
                                    ->info('Route prefix for security-related endpoints (login, registration, etc.).')
                                    ->defaultValue('auth')
                                    ->treatNullLike('auth')
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
                        // URL support
                        // ──────────────────────────────
                        ->arrayNode('url')
                            ->info('URL Support (in response) for this authentication collection.')
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
                        // Template
                        // ──────────────────────────────
                        ->arrayNode('templates')
                            ->info('Paths to the response template files used as models for formatting the API output for lists and single items.')
                            ->addDefaultsIfNotSet()->children()

                                ->scalarNode('account')
                                    ->info('Path to the response template file used as a model for formatting the API output for account operations.')
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
                        // REST endpoints
                        // ──────────────────────────────
                        ->arrayNode('endpoints')
                            ->info('Configure the endpoints available for this collection. Default: index, create, read, update, delete.')
                            ->addDefaultsIfNotSet()->children()

                                // Register endpoint
                                ->arrayNode('register')
                                    ->info('Defines the registration endpoint settings.')
                                    ->addDefaultsIfNotSet()->children()

                                        // ──────────────────────────────
                                        // Enabled
                                        // ──────────────────────────────
                                        ->booleanNode('enabled')
                                            ->info('Enable or disable registration.')
                                            ->defaultFalse()
                                            ->treatNullLike(false)
                                        ->end()

                                        // ──────────────────────────────
                                        // Route config
                                        // ──────────────────────────────
                                        ->arrayNode('route')
                                            ->info('Defines the HTTP configuration for the endpoint: route name, path, HTTP methods, controller, constraints, and routing options.')
                                            ->addDefaultsIfNotSet()
                                            ->children()

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
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([Request::METHOD_POST])
                                                    ->treatNullLike([Request::METHOD_POST])
                                                ->end()

                                                ->scalarNode('controller')
                                                    ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                    ->defaultValue('OSW3\Api\Controller\Auth\RegisterController::register')
                                                    ->treatNullLike('OSW3\Api\Controller\Auth\RegisterController::register')
                                                    ->validate()
                                                        ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                        ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                    ->end()
                                                ->end()

                                                ->arrayNode('options')
                                                    ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
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
                                        // Properties
                                        // ──────────────────────────────
                                        ->arrayNode('properties')
                                            ->info('Registration fields mapping.')
                                            ->addDefaultsIfNotSet()->children()

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

                                    ->end()
                                ->end()
                                
                                // Login endpoint
                                ->arrayNode('login')
                                    ->info('Defines the login endpoint settings.')
                                    ->addDefaultsIfNotSet()->children()

                                        // ──────────────────────────────
                                        // Enabled
                                        // ──────────────────────────────
                                        ->booleanNode('enabled')
                                            ->info('Enable or disable login.')
                                            ->defaultFalse()
                                            ->treatNullLike(false)
                                        ->end()

                                        // ──────────────────────────────
                                        // Route config
                                        // ──────────────────────────────
                                        ->arrayNode('route')
                                            ->info('Defines the HTTP configuration for the endpoint: route name, path, HTTP methods, controller, constraints, and routing options.')
                                            ->addDefaultsIfNotSet()
                                            ->children()

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
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([Request::METHOD_POST])
                                                    ->treatNullLike([Request::METHOD_POST])
                                                ->end()

                                                ->scalarNode('controller')
                                                    ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                    ->defaultValue('OSW3\Api\Controller\Auth\LoginController::login')
                                                    ->treatNullLike('OSW3\Api\Controller\Auth\LoginController::login')
                                                    ->validate()
                                                        ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                        ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                    ->end()
                                                ->end()

                                                ->arrayNode('options')
                                                    ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
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
                                        // Properties
                                        // ──────────────────────────────
                                        ->arrayNode('properties')
                                            ->info('Login fields mapping.')
                                            ->addDefaultsIfNotSet()->children()

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

                                                ->scalarNode('rememberMe')
                                                    ->info('Remember Me field for the User entity (e.g., "rememberMe").')
                                                    ->defaultValue('rememberMe')
                                                    ->treatNullLike('rememberMe')
                                                ->end()

                                            ->end()
                                        ->end()

                                    ->end()
                                ->end()
                                
                                // Logout endpoint
                                ->arrayNode('logout')
                                    ->info('Defines the logout endpoint settings.')
                                    ->addDefaultsIfNotSet()->children()

                                        // ──────────────────────────────
                                        // Enabled
                                        // ──────────────────────────────
                                        ->booleanNode('enabled')
                                            ->info('Enable or disable logout.')
                                            ->defaultFalse()
                                            ->treatNullLike(false)
                                        ->end()

                                        // ──────────────────────────────
                                        // Route config
                                        // ──────────────────────────────
                                        ->arrayNode('route')
                                            ->info('Defines the HTTP configuration for the endpoint: route name, path, HTTP methods, controller, constraints, and routing options.')
                                            ->addDefaultsIfNotSet()
                                            ->children()

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
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([Request::METHOD_POST])
                                                    ->treatNullLike([Request::METHOD_POST])
                                                ->end()

                                                ->scalarNode('controller')
                                                    ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                    ->defaultValue('OSW3\Api\Controller\Auth\LogoutController::logout')
                                                    ->treatNullLike('OSW3\Api\Controller\Auth\LogoutController::logout')
                                                    ->validate()
                                                        ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                        ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                    ->end()
                                                ->end()

                                                ->arrayNode('options')
                                                    ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
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

                                    ->end()
                                ->end()
                                
                                // Logout all sessions endpoint
                                ->arrayNode('logout_all')
                                    ->info('Defines the logout all sessions endpoint settings.')
                                    ->addDefaultsIfNotSet()->children()

                                        // ──────────────────────────────
                                        // Enabled
                                        // ──────────────────────────────
                                        ->booleanNode('enabled')
                                            ->info('Enable or disable logout all sessions.')
                                            ->defaultFalse()
                                            ->treatNullLike(false)
                                        ->end()

                                        // ──────────────────────────────
                                        // Route config
                                        // ──────────────────────────────
                                        ->arrayNode('route')
                                            ->info('Defines the HTTP configuration for the endpoint: route name, path, HTTP methods, controller, constraints, and routing options.')
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
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([Request::METHOD_POST])
                                                    ->treatNullLike([Request::METHOD_POST])
                                                ->end()

                                                ->scalarNode('controller')
                                                    ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                    ->defaultValue('OSW3\Api\Controller\Auth\LogoutController::logoutAll')
                                                    ->treatNullLike('OSW3\Api\Controller\Auth\LogoutController::logoutAll')
                                                    ->validate()
                                                        ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                        ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                    ->end()
                                                ->end()

                                                ->arrayNode('options')
                                                    ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
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

                                    ->end()
                                ->end()
                                
                                // Refresh token endpoint
                                ->arrayNode('refresh')
                                    ->info('Defines the refresh token endpoint settings.')
                                    ->addDefaultsIfNotSet()->children()

                                        // ──────────────────────────────
                                        // Enabled
                                        // ──────────────────────────────
                                        ->booleanNode('enabled')
                                            ->info('Enable or disable refresh token.')
                                            ->defaultFalse()
                                            ->treatNullLike(false)
                                        ->end()

                                        // ──────────────────────────────
                                        // Route config
                                        // ──────────────────────────────
                                        ->arrayNode('route')
                                            ->info('Defines the HTTP configuration for the endpoint: route name, path, HTTP methods, controller, constraints, and routing options.')
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
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([Request::METHOD_POST])
                                                    ->treatNullLike([Request::METHOD_POST])
                                                ->end()

                                                ->scalarNode('controller')
                                                    ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                    ->defaultValue('OSW3\Api\Controller\Auth\LoginController::refresh')
                                                    ->treatNullLike('OSW3\Api\Controller\Auth\LoginController::refresh')
                                                    ->validate()
                                                        ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                        ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                    ->end()
                                                ->end()

                                                ->arrayNode('options')
                                                    ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
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

                                    ->end()
                                ->end()
                                
                                // Email verification endpoint
                                ->arrayNode('email_verification')
                                    ->info('Defines the email verification endpoint settings.')
                                    ->addDefaultsIfNotSet()->children()

                                        // ──────────────────────────────
                                        // Enabled
                                        // ──────────────────────────────
                                        ->booleanNode('enabled')
                                            ->info('Enable or disable email verification.')
                                            ->defaultFalse()
                                            ->treatNullLike(false)
                                        ->end()

                                        // ──────────────────────────────
                                        // Route config
                                        // ──────────────────────────────
                                        ->arrayNode('route')
                                            ->info('Defines the HTTP configuration for the endpoint: route name, path, HTTP methods, controller, constraints, and routing options.')
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
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([Request::METHOD_POST])
                                                    ->treatNullLike([Request::METHOD_POST])
                                                ->end()

                                                ->scalarNode('controller')
                                                    ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                    ->defaultValue('OSW3\Api\Controller\Auth\EmailVerificationController::verify')
                                                    ->treatNullLike('OSW3\Api\Controller\Auth\EmailVerificationController::verify')
                                                    ->validate()
                                                        ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                        ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                    ->end()
                                                ->end()

                                                ->arrayNode('options')
                                                    ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
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
                                        // Properties
                                        // ──────────────────────────────
                                        ->arrayNode('properties')
                                            ->info('Email verification fields mapping.')
                                            ->addDefaultsIfNotSet()->children()

                                                ->scalarNode('token')
                                                    ->info('Token field for the User entity (e.g., "token").')
                                                    ->defaultValue('token')
                                                    ->treatNullLike('token')
                                                ->end()

                                            ->end()
                                        ->end()

                                    ->end()
                                ->end()
                                
                                // Email resend verification endpoint
                                ->arrayNode('email_resend')
                                    ->info('Defines the email resend verification endpoint settings.')
                                    ->addDefaultsIfNotSet()->children()

                                        // ──────────────────────────────
                                        // Enabled
                                        // ──────────────────────────────
                                        ->booleanNode('enabled')
                                            ->info('Enable or disable email resend verification.')
                                            ->defaultFalse()
                                            ->treatNullLike(false)
                                        ->end()

                                        // ──────────────────────────────
                                        // Route config
                                        // ──────────────────────────────
                                        ->arrayNode('route')
                                            ->info('Defines the HTTP configuration for the endpoint: route name, path, HTTP methods, controller, constraints, and routing options.')
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
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([Request::METHOD_POST])
                                                    ->treatNullLike([Request::METHOD_POST])
                                                ->end()

                                                ->scalarNode('controller')
                                                    ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                    ->defaultValue('OSW3\Api\Controller\Auth\EmailVerificationController::resend')
                                                    ->treatNullLike('OSW3\Api\Controller\Auth\EmailVerificationController::resend')
                                                    ->validate()
                                                        ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                        ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                    ->end()
                                                ->end()

                                                ->arrayNode('options')
                                                    ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
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
                                        // Properties
                                        // ──────────────────────────────
                                        ->arrayNode('properties')
                                            ->info('Email verification resend fields mapping.')
                                            ->addDefaultsIfNotSet()->children()

                                                ->scalarNode('email')
                                                    ->info('Email field for the User entity (e.g., "email").')
                                                    ->defaultValue('email')
                                                    ->treatNullLike('email')
                                                ->end()

                                            ->end()
                                        ->end()

                                    ->end()
                                ->end()

                                // Password reset request endpoint
                                ->arrayNode('password_reset_request')
                                    ->info('Defines the password reset request endpoint settings.')
                                    ->addDefaultsIfNotSet()->children()

                                        // ──────────────────────────────
                                        // Enabled
                                        // ──────────────────────────────
                                        ->booleanNode('enabled')
                                            ->info('Enable or disable password reset request.')
                                            ->defaultFalse()
                                            ->treatNullLike(false)
                                        ->end()

                                        // ──────────────────────────────
                                        // Route config
                                        // ──────────────────────────────
                                        ->arrayNode('route')
                                            ->info('Defines the HTTP configuration for the endpoint: route name, path, HTTP methods, controller, constraints, and routing options.')
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
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([Request::METHOD_POST])
                                                    ->treatNullLike([Request::METHOD_POST])
                                                ->end()

                                                ->scalarNode('controller')
                                                    ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                    ->defaultValue('OSW3\Api\Controller\Auth\PasswordController::request')
                                                    ->treatNullLike('OSW3\Api\Controller\Auth\PasswordController::request')
                                                    ->validate()
                                                        ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                        ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                    ->end()
                                                ->end()

                                                ->arrayNode('options')
                                                    ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
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
                                        // Properties
                                        // ──────────────────────────────
                                        ->arrayNode('properties')
                                            ->info('Password reset fields mapping.')
                                            ->addDefaultsIfNotSet()->children()

                                                ->scalarNode('email')
                                                    ->info('Email field for the User entity (e.g., "email").')
                                                    ->defaultNull()
                                                ->end()

                                            ->end()
                                        ->end()

                                    ->end()
                                ->end()

                                // Password reset endpoint
                                ->arrayNode('password_reset')
                                    ->info('Defines the password reset endpoint settings.')
                                    ->addDefaultsIfNotSet()->children()

                                        // ──────────────────────────────
                                        // Enabled
                                        // ──────────────────────────────
                                        ->booleanNode('enabled')
                                            ->info('Enable or disable password reset.')
                                            ->defaultFalse()
                                            ->treatNullLike(false)
                                        ->end()

                                        // ──────────────────────────────
                                        // Route config
                                        // ──────────────────────────────
                                        ->arrayNode('route')
                                            ->info('Defines the HTTP configuration for the endpoint: route name, path, HTTP methods, controller, constraints, and routing options.')
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
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([Request::METHOD_POST])
                                                    ->treatNullLike([Request::METHOD_POST])
                                                ->end()

                                                ->scalarNode('controller')
                                                    ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                    ->defaultValue('OSW3\Api\Controller\Auth\PasswordController::reset')
                                                    ->treatNullLike('OSW3\Api\Controller\Auth\PasswordController::reset')
                                                    ->validate()
                                                        ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                        ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                    ->end()
                                                ->end()

                                                ->arrayNode('options')
                                                    ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
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
                                        // Properties
                                        // ──────────────────────────────
                                        ->arrayNode('properties')
                                            ->info('Registration fields mapping.')
                                            ->addDefaultsIfNotSet()->children()

                                                ->scalarNode('token')
                                                    ->info('Token field for the User entity (e.g., "token").')
                                                    ->defaultValue('token')
                                                    ->treatNullLike('token')
                                                ->end()

                                                ->scalarNode('password')
                                                    ->info('Password field for the User entity (e.g., "password").')
                                                    ->defaultValue('password')
                                                    ->treatNullLike('password')
                                                ->end()

                                                ->scalarNode('confirm')
                                                    ->info('Confirm password field for the User entity (e.g., "confirm").')
                                                    ->defaultValue('confirm')
                                                    ->treatNullLike('confirm')
                                                ->end()

                                            ->end()
                                        ->end()

                                    ->end()
                                ->end()

                                // Password change endpoint
                                ->arrayNode('password_change')
                                    ->info('Defines the password change endpoint settings.')
                                    ->addDefaultsIfNotSet()->children()

                                        // ──────────────────────────────
                                        // Enabled
                                        // ──────────────────────────────
                                        ->booleanNode('enabled')
                                            ->info('Enable or disable password change.')
                                            ->defaultFalse()
                                            ->treatNullLike(false)
                                        ->end()

                                        // ──────────────────────────────
                                        // Route config
                                        // ──────────────────────────────
                                        ->arrayNode('route')
                                            ->info('Defines the HTTP configuration for the endpoint: route name, path, HTTP methods, controller, constraints, and routing options.')
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
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([Request::METHOD_POST])
                                                    ->treatNullLike([Request::METHOD_POST])
                                                ->end()

                                                ->scalarNode('controller')
                                                    ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                    ->defaultValue('OSW3\Api\Controller\Auth\PasswordController::change')
                                                    ->treatNullLike('OSW3\Api\Controller\Auth\PasswordController::change')
                                                    ->validate()
                                                        ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                        ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                    ->end()
                                                ->end()

                                                ->arrayNode('options')
                                                    ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
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
                                        // Properties
                                        // ──────────────────────────────
                                        ->arrayNode('properties')
                                            ->info('Password change fields mapping.')
                                            ->addDefaultsIfNotSet()->children()

                                                ->scalarNode('current')
                                                    ->info('Current password field for the User entity (e.g., "currentPassword").')
                                                    ->defaultValue('current')
                                                    ->treatNullLike('current')
                                                ->end()

                                                ->scalarNode('password')
                                                    ->info('New password field for the User entity (e.g., "password").')
                                                    ->defaultValue('password')
                                                    ->treatNullLike('password')
                                                ->end()

                                                ->scalarNode('confirm')
                                                    ->info('Confirm password field for the User entity (e.g., "confirmPassword").')
                                                    ->defaultValue('confirm')
                                                    ->treatNullLike('confirm')
                                                ->end()

                                            ->end()
                                        ->end()

                                    ->end()
                                ->end()

                                // Account endpoint
                                ->arrayNode('account')
                                    ->info('Defines the account endpoint settings.')
                                    ->addDefaultsIfNotSet()->children()

                                        // ──────────────────────────────
                                        // Enabled
                                        // ──────────────────────────────
                                        ->booleanNode('enabled')
                                            ->info('Enable or disable account endpoint.')
                                            ->defaultFalse()
                                            ->treatNullLike(false)
                                        ->end()

                                        // ──────────────────────────────
                                        // Route config
                                        // ──────────────────────────────
                                        ->arrayNode('route')
                                            ->info('Defines the HTTP configuration for the endpoint: route name, path, HTTP methods, controller, constraints, and routing options.')
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
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([Request::METHOD_POST])
                                                    ->treatNullLike([Request::METHOD_POST])
                                                ->end()

                                                ->scalarNode('controller')
                                                    ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                    ->defaultValue('OSW3\Api\Controller\Auth\AccountController::account')
                                                    ->treatNullLike('OSW3\Api\Controller\Auth\AccountController::account')
                                                    ->validate()
                                                        ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                        ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                    ->end()
                                                ->end()

                                                ->arrayNode('options')
                                                    ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
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

                                    ->end()
                                ->end()

                                // Profile endpoint
                                ->arrayNode('profile')
                                    ->info('Defines the profile endpoint settings.')
                                    ->addDefaultsIfNotSet()->children()

                                        // ──────────────────────────────
                                        // Enabled
                                        // ──────────────────────────────
                                        ->booleanNode('enabled')
                                            ->info('Enable or disable profile endpoint.')
                                            ->defaultFalse()
                                            ->treatNullLike(false)
                                        ->end()

                                        // ──────────────────────────────
                                        // Route config
                                        // ──────────────────────────────
                                        ->arrayNode('route')
                                            ->info('Defines the HTTP configuration for the endpoint: route name, path, HTTP methods, controller, constraints, and routing options.')
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
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([Request::METHOD_POST])
                                                    ->treatNullLike([Request::METHOD_POST])
                                                ->end()

                                                ->scalarNode('controller')
                                                    ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                    ->defaultValue('OSW3\Api\Controller\Auth\AccountController::profile')
                                                    ->treatNullLike('OSW3\Api\Controller\Auth\AccountController::profile')
                                                    ->validate()
                                                        ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                        ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                    ->end()
                                                ->end()

                                                ->arrayNode('options')
                                                    ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
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
                                        // Properties
                                        // ──────────────────────────────
                                        ->arrayNode('properties')
                                            ->info('Profile fields mapping.')
                                            ->addDefaultsIfNotSet()->children()

                                                ->scalarNode('first_name')
                                                    ->info('First name field for the User entity (e.g., "firstName").')
                                                    ->defaultValue('firstName')
                                                    ->treatNullLike('firstName')
                                                ->end()

                                                ->scalarNode('last_name')
                                                    ->info('Last name field for the User entity (e.g., "lastName").')
                                                    ->defaultValue('lastName')
                                                    ->treatNullLike('lastName')
                                                ->end()

                                                ->scalarNode('avatar')
                                                    ->info('Avatar field for the User entity (e.g., "avatar").')
                                                    ->defaultValue('avatar')
                                                    ->treatNullLike('avatar')
                                                ->end()

                                                ->scalarNode('phone')
                                                    ->info('Phone field for the User entity (e.g., "phone").')
                                                    ->defaultValue('phone')
                                                    ->treatNullLike('phone')
                                                ->end()

                                                ->scalarNode('birth_date')
                                                    ->info('Birth date field for the User entity (e.g., "birthDate").')
                                                    ->defaultValue('birthDate')
                                                    ->treatNullLike('birthDate')
                                                ->end()

                                            ->end()
                                        ->end()

                                    ->end()
                                ->end()

                                // 2FA enable endpoint
                                ->arrayNode('2fa_enable')
                                    ->info('Defines the 2FA enable endpoint settings.')
                                    ->addDefaultsIfNotSet()->children()

                                        // ──────────────────────────────
                                        // Enabled
                                        // ──────────────────────────────
                                        ->booleanNode('enabled')
                                            ->info('Enable or disable 2FA enable endpoint.')
                                            ->defaultFalse()
                                            ->treatNullLike(false)
                                        ->end()

                                        // ──────────────────────────────
                                        // Route config
                                        // ──────────────────────────────
                                        ->arrayNode('route')
                                            ->info('Defines the HTTP configuration for the endpoint: route name, path, HTTP methods, controller, constraints, and routing options.')
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
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([Request::METHOD_POST])
                                                    ->treatNullLike([Request::METHOD_POST])
                                                ->end()

                                                ->scalarNode('controller')
                                                    ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                    ->defaultValue('OSW3\Api\Controller\Auth\TwoFactorAuthController::enabled')
                                                    ->treatNullLike('OSW3\Api\Controller\Auth\TwoFactorAuthController::enabled')
                                                    ->validate()
                                                        ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                        ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                    ->end()
                                                ->end()

                                                ->arrayNode('options')
                                                    ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
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
                                        // Properties
                                        // ──────────────────────────────
                                        ->arrayNode('properties')
                                            ->info('2FA enable fields mapping.')
                                            ->addDefaultsIfNotSet()->children()

                                                ->scalarNode('password')
                                                    ->info('Password field for the User entity (e.g., "password").')
                                                    ->defaultValue('password')
                                                    ->treatNullLike('password')
                                                ->end()

                                                ->scalarNode('code')
                                                    ->info('Code field for the User entity (e.g., "code").')
                                                    ->defaultValue('code')
                                                    ->treatNullLike('code')
                                                ->end()

                                            ->end()
                                        ->end()

                                    ->end()
                                ->end()

                                // 2FA disable endpoint
                                ->arrayNode('2fa_disable')
                                    ->info('Defines the 2FA disable endpoint settings.')
                                    ->addDefaultsIfNotSet()->children()

                                        // ──────────────────────────────
                                        // Enabled
                                        // ──────────────────────────────
                                        ->booleanNode('enabled')
                                            ->info('Enable or disable 2FA disable endpoint.')
                                            ->defaultFalse()
                                            ->treatNullLike(false)
                                        ->end()

                                        // ──────────────────────────────
                                        // Route config
                                        // ──────────────────────────────
                                        ->arrayNode('route')
                                            ->info('Defines the HTTP configuration for the endpoint: route name, path, HTTP methods, controller, constraints, and routing options.')
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
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([Request::METHOD_POST])
                                                    ->treatNullLike([Request::METHOD_POST])
                                                ->end()

                                                ->scalarNode('controller')
                                                    ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                    ->defaultValue('OSW3\Api\Controller\Auth\TwoFactorAuthController::disabled')
                                                    ->treatNullLike('OSW3\Api\Controller\Auth\TwoFactorAuthController::disabled')
                                                    ->validate()
                                                        ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                        ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                    ->end()
                                                ->end()

                                                ->arrayNode('options')
                                                    ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
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
                                        // Properties
                                        // ──────────────────────────────
                                        ->arrayNode('properties')
                                            ->info('Registration fields mapping. Override the default identifier and password fields if necessary.')
                                            ->addDefaultsIfNotSet()->children()

                                                ->scalarNode('code')
                                                    ->info('Code field for the User entity (e.g., "code").')
                                                    ->defaultValue('code')
                                                    ->treatNullLike('code')
                                                ->end()

                                            ->end()
                                        ->end()

                                    ->end()
                                ->end()

                                // 2FA verify endpoint
                                ->arrayNode('2fa_verify')
                                    ->info('Defines the 2FA verify endpoint settings.')
                                    ->addDefaultsIfNotSet()->children()

                                        // ──────────────────────────────
                                        // Enabled
                                        // ──────────────────────────────
                                        ->booleanNode('enabled')
                                            ->info('Enable or disable 2FA verify endpoint.')
                                            ->defaultFalse()
                                            ->treatNullLike(false)
                                        ->end()

                                        // ──────────────────────────────
                                        // Route config
                                        // ──────────────────────────────
                                        ->arrayNode('route')
                                            ->info('Defines the HTTP configuration for the endpoint: route name, path, HTTP methods, controller, constraints, and routing options.')
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
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([Request::METHOD_POST])
                                                    ->treatNullLike([Request::METHOD_POST])
                                                ->end()

                                                ->scalarNode('controller')
                                                    ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                    ->defaultValue('OSW3\Api\Controller\Auth\TwoFactorAuthController::verify')
                                                    ->treatNullLike('OSW3\Api\Controller\Auth\TwoFactorAuthController::verify')
                                                    ->validate()
                                                        ->ifTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                        ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                    ->end()
                                                ->end()

                                                ->arrayNode('options')
                                                    ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
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
                                        // Properties
                                        // ──────────────────────────────
                                        ->arrayNode('properties')
                                            ->info('Registration fields mapping. Override the default identifier and password fields if necessary.')
                                            ->addDefaultsIfNotSet()->children()

                                                ->scalarNode('password')
                                                    ->info('Password field for the User entity (e.g., "password").')
                                                    ->defaultValue('password')
                                                    ->treatNullLike('password')
                                                ->end()

                                            ->end()
                                        ->end()

                                    ->end()
                                ->end()

                            ->end()
                        ->end()

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
                    ->children()

                        // ──────────────────────────────
                        // Enabled
                        // ──────────────────────────────
                        ->booleanNode('enabled')
                            ->info('Enable or disable this collection.')
                            ->defaultNull()
                        ->end()

                        // ──────────────────────────────
                        // Deprecation
                        // ──────────────────────────────
                        ->arrayNode('deprecation')
                            ->info('API deprecation notices for this collection.')
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

                                            ->scalarNode('delete')
                                                ->info('Path to the response template file used as a model for formatting delete responses.')
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
            // Documentation
            // ──────────────────────────────
			// ->arrayNode('documentation')
            //     ->info('API documentation configuration')
            //     ->addDefaultsIfNotSet()->children()

            //         ->booleanNode('enable')
            //             ->info('Enable or disable the documentation for this API provider.')
            //             ->defaultFalse()
            //         ->end()

            //         ->scalarNode('prefix')
            //             ->info('Path prefix')
            //             ->defaultValue('_documentation')
            //         ->end()

            //     ->end()
            // ->end()

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

            // Route
            ProviderRoutePrefixResolver::resolve($providers);
            
            // Authentication
            ProviderAuthenticationNameResolver::resolve($providers);
            // ProviderAuthenticationRoutePrefixResolver::resolve($providers);


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