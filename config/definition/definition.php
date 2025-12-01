<?php

use OSW3\Api\Enum\MimeType;
use OSW3\Api\Enum\MergeStrategy;
use OSW3\Api\Resolver\ApiResolver;
use OSW3\Api\Resolver\NameResolver;
use OSW3\Api\Resolver\RouteResolver;
use OSW3\Api\Validator\EntityValidator;
use OSW3\Api\Resolver\IsEnabledResolver;
use OSW3\Api\Resolver\RateLimitResolver;
use OSW3\Api\Resolver\TemplatesResolver;
use OSW3\Api\Resolver\PaginationResolver;
use OSW3\Api\Resolver\UrlSupportResolver;
use OSW3\Api\Resolver\DeprecationResolver;
use OSW3\Api\Validator\ControllerValidator;
use OSW3\Api\Resolver\AccessControlResolver;
use OSW3\Api\Resolver\SerializationResolver;
use OSW3\Api\Validator\TransformerValidator;
use Symfony\Component\HttpFoundation\Request;

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

                    // Deprecation enabled
                    ->booleanNode('enabled')
                        ->info('Enable or disable the deprecation for this API provider.')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()

                    // Deprecation start date
                    ->scalarNode('start_at')
                        ->info('Deprecation since date')
                        ->defaultNull()
                    ->end()

                    // Deprecation removal date
                    ->scalarNode('sunset_at')
                        ->info('Deprecation removal date')
                        ->defaultNull()
                    ->end()

                    // Deprecation link
                    ->scalarNode('link')
                        ->info('Deprecation link')
                        ->defaultNull()
                    ->end()

                    // Deprecation successor link
                    ->scalarNode('successor')
                        ->info('Deprecation successor link')
                        ->defaultNull()
                    ->end()

                    // Deprecation message
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

                    // Version number
                    ->scalarNode('number')
                        ->info('Version number (null = auto-assigned)')
                        ->defaultNull()
                    ->end()

                    // Version prefix
                    ->scalarNode('prefix')
                        ->info('Version prefix (e.g. "v")')
                        ->defaultValue('v')
                        ->treatNullLike('v')
                    ->end()

                    // Version location
                    ->enumNode('location')
                        ->info('How the version is exposed: in URL path, HTTP header, query parameter, or subdomain.')
                        ->values(['path', 'header', 'param', 'subdomain'])
                        ->defaultValue('path')
                        ->treatNullLike('path')
                    ->end()

                    // Beta flag
                    ->booleanNode('beta')
                        ->info('Indicates whether this API version is in beta. If true, clients should be aware that the API may change.')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()

                    // Version directive
                    ->scalarNode('directive')
                        ->info('Defines the HTTP header used for API versioning.')
                        ->defaultValue("Accept")
                        ->treatNullLike('Accept')
                    ->end()

                    // Version pattern
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

                    // Route name pattern
                    ->scalarNode('pattern')
                        ->info('Pattern for route names. Available placeholders: {version}, {collection}, {action}.')
                        ->defaultValue('api_{version}_{collection}_{action}')
                        ->treatNullLike('api_{version}_{collection}_{action}')
                    ->end()

                    // Route URL prefix
                    ->scalarNode('prefix')
                        ->info('Default URL prefix for all routes in this API version.')
                        ->defaultValue('api')
                        ->treatNullLike('api')
                    ->end()

                    // Route hostnames
                    ->arrayNode('hosts')
                        ->info('List of hostnames for this API provider (e.g. api.example.com).')
                        ->normalizeKeys(false)
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()

                    // Route schemes
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

                    // Enable or disable pagination
                    ->booleanNode('enabled')
                        ->info('Enable or disable pagination for all collections.')
                        ->defaultTrue()
                        ->treatNullLike(true)
                    ->end()

                    // Default number of items returned per page
                    ->integerNode('limit')
                        ->info('Limit the number of items returned per page.')
                        ->defaultValue(10)
                        ->treatNullLike(10)
                        ->min(1)
                    ->end()

                    // Maximum number of items returned per page
                    ->integerNode('max_limit')
                        ->info('Maximum number of items returned per page.')
                        ->defaultValue(100)
                        ->treatNullLike(100)
                        ->min(1)
                    ->end()

                    // Allow limit override
                    ->booleanNode('allow_limit_override')
                        ->info('Allow overriding the "limit" parameter via URL (e.g. ?limit=50).')
                        ->defaultTrue()
                        ->treatNullLike(true)
                    ->end()

                    // Pagination parameter names
                    ->arrayNode('parameters')
                        ->info('Query parameter names for pagination.')
                        ->addDefaultsIfNotSet()->children()

                            // Page parameter name
                            ->scalarNode('page')
                                ->info('Parameter name for the page number.')
                                ->defaultValue('page')
                                ->treatNullLike('page')
                            ->end()

                            // Limit parameter name
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

                    // Support URLs in response
                    ->booleanNode('support')
                        ->info('Whether to include URL elements in API responses.')
                        ->defaultTrue()
                        ->treatNullLike(true)
                    ->end()

                    // Absolute URLs
                    ->booleanNode('absolute')
                        ->info('Generate absolute URLs if true, relative otherwise')
                        ->defaultTrue()
                        ->treatNullLike(true)
                    ->end()

                    // URL property name
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

                    // Enable or disable rate limiting
                    ->booleanNode('enabled')
                        ->info('Enable or disable rate limiting for this API provider.')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()

                    // Global rate limit
                    ->scalarNode('limit')
                        ->info('Maximum number of requests allowed in the specified time window.')
                        ->defaultValue('100/hour')
                        ->treatNullLike('100/hour')
                    ->end()

                    // Specific rate limits based on user roles
                    ->arrayNode('by_role')
                        ->info('Specific rate limits based on user roles.')
                        ->normalizeKeys(false)
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()

                    // Specific rate limits for individual users
                    ->arrayNode('by_user')
                        ->info('Specific rate limits for individual users identified by user ID or username.')
                        ->normalizeKeys(false)
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()

                    // Specific rate limits based on client IP addresses
                    ->arrayNode('by_ip')
                        ->info('Specific rate limits based on client IP addresses.')
                        ->normalizeKeys(false)
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()

                    // Specific rate limits for different application keys or API clients
                    ->arrayNode('by_application')
                        ->info('Specific rate limits for different application keys or API clients.')
                        ->normalizeKeys(false)
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()

                    // Include rate limit headers in responses
                    ->booleanNode('include_headers')
                        ->info('Whether to include rate limit headers in responses.')
                        ->defaultTrue()
                        ->treatNullLike(true)
                    ->end()

                ->end()
            ->end()

            // ──────────────────────────────
            // Template
            // ──────────────────────────────
            ->arrayNode('templates')
                ->info('Paths to the response template files used as models for formatting the API output for lists and single items.')
                ->addDefaultsIfNotSet()->children()

                    // List template path
                    ->scalarNode('list')
                        ->info('Path to the response template file used as a model for formatting the API output for lists.')
                        ->defaultValue('Resources/templates/yaml/list.yaml')
                        ->treatNullLike('Resources/templates/yaml/list.yaml')
                    ->end()

                    // Single item template path
                    ->scalarNode('single')
                        ->info('Path to the response template file used as a model for formatting the API output for single items.')
                        ->defaultValue('Resources/templates/yaml/single.yaml')
                        ->treatNullLike('Resources/templates/yaml/single.yaml')
                    ->end()

                    // Delete operation template path
                    ->scalarNode('delete')
                        ->info('Path to the response template file used as a model for formatting the API output for delete operations.')
                        ->defaultValue('Resources/templates/yaml/delete.yaml')
                        ->treatNullLike('Resources/templates/yaml/delete.yaml')
                    ->end()

                    // Account operation template path
                    ->scalarNode('account')
                        ->info('Path to the response template file used as a model for formatting the API output for account operations.')
                        ->defaultValue('Resources/templates/yaml/account.yaml')
                        ->treatNullLike('Resources/templates/yaml/account.yaml')
                    ->end()

                    // Error response template path
                    ->scalarNode('error')
                        ->info('Path to the response template file used as a model for formatting error responses.')
                        ->defaultValue('Resources/templates/yaml/error.yaml')
                        ->treatNullLike('Resources/templates/yaml/error.yaml')
                    ->end()

                    // Not found response template path
                    ->scalarNode('not_found')
                        ->info('Path to the response template file used as a model for formatting not found responses (e.g. 404 Not Found).')
                        ->defaultValue('Resources/templates/yaml/not_found.yaml')
                        ->treatNullLike('Resources/templates/yaml/not_found.yaml')
                    ->end()

                    // Login response template path
                    ->scalarNode('login')
                        ->info('Path to the response template file used as a model for formatting login responses.')
                        ->defaultValue('Resources/templates/yaml/login.yaml')
                        ->treatNullLike('Resources/templates/yaml/login.yaml')
                    ->end()

                ->end()
            ->end()

            // ──────────────────────────────
            // Response 
            // ──────────────────────────────
			->arrayNode('response')
                ->info('Settings related to API response formatting, including templates, default format, caching, and headers.')
                ->addDefaultsIfNotSet()->children()

                    // Response format
                    ->arrayNode('format')
                        ->info('Response format settings.')
                        ->addDefaultsIfNotSet()->children()

                            // Response format type
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

                            // MIME type override
                            ->scalarNode('mime_type')
                                ->info('Override the MIME type of the response format.')
                                ->defaultNull()
                            ->end()

                        ->end()
                    ->end()

                    // Content negotiation settings
                    ->arrayNode('content_negotiation')
                        ->info('Content negotiation settings for API responses.')
                        ->addDefaultsIfNotSet()->children()

                            // Enable format override via URL parameter
                            ->booleanNode('enabled')
                                ->info('If true, allows clients to override the response format using a URL parameter (e.g. ?format=xml).')
                                ->defaultFalse()
                                ->treatNullLike(false)
                            ->end()

                            // URL parameter name for format override
                            ->scalarNode('parameter')
                                ->info('Name of the URL parameter used to override the response format.')
                                ->defaultValue('_format')
                                ->treatNullLike('_format')
                            ->end()

                        ->end()
                    ->end()

                    // Security settings
                    ->arrayNode('security')
                        ->info('Security settings for API responses.')
                        ->addDefaultsIfNotSet()->children()

                            // Response checksum/hash settings
                            ->arrayNode('checksum')
                                ->info('Configuration for response checksum/hash settings.')
                                ->addDefaultsIfNotSet()->children()
        
                                    // Enable response checksum/hash verification
                                    ->booleanNode('enabled')
                                        ->info('If true, enables response checksum/hash verification.')
                                        ->defaultTrue()
                                        ->treatNullLike(true)
                                    ->end()
        
                                    // Hash algorithm to use for response hashing
                                    ->enumNode('algorithm')
                                        ->info('Hash algorithm to use for response hashing. Options include "md5", "sha1", "sha256", etc.')
                                        ->values(['sha1', 'sha256', 'sha512'])
                                        ->defaultValue('sha256')
                                        ->treatNullLike('sha256')
                                    ->end()
        
                                ->end()
                            ->end()

                        ->end()
                    ->end()

                    // Cache-Control settings
                    ->arrayNode('cache_control')
                        ->info('List of Cache-Control directives to include in responses.')
                        ->addDefaultsIfNotSet()->children()

                            // Enable Cache-Control headers
                            ->booleanNode('enabled')
                                ->info('If true, enables Cache-Control headers.')
                                ->defaultFalse()
                                ->treatNullLike(false)
                            ->end()

                            // Public Cache-Control directive
                            ->booleanNode('public')
                                ->info('If true, sets Cache-Control to "public", allowing shared caches. If false, sets to "private".')
                                ->defaultFalse()
                                ->treatNullLike(false)
                            ->end()

                            // No store Cache-Control directive
                            ->booleanNode('no_store')
                                ->info('If true, adds "no-store" to Cache-Control.')
                                ->defaultFalse()
                                ->treatNullLike(false)
                            ->end()

                            // Must revalidate Cache-Control directive
                            ->booleanNode('must_revalidate')
                                ->info('If true, adds "must-revalidate" to Cache-Control.')
                                ->defaultTrue()
                                ->treatNullLike(true)
                            ->end()

                            // Max age in seconds (0 = no cache)
                            ->integerNode('max_age')
                                ->info('Max age in seconds (0 = no cache).')
                                ->defaultValue(3600)
                                ->treatNullLike(3600)
                                ->min(0)
                                ->max(31536000)
                            ->end()

                        ->end()
                    ->end()

                    // CORS configuration
                    ->arrayNode('cors')
                        ->info('CORS configuration for the API.')
                        ->addDefaultsIfNotSet()->children()

                            // Enable or disable CORS
                            ->booleanNode('enabled')
                                ->info('Enable or disable CORS for the API.')
                                ->defaultTrue()
                                ->treatNullLike(true)
                            ->end()

                            // Allowed origins for CORS requests
                            ->arrayNode('origins')
                                ->info('List of allowed origins for CORS requests.')
                                ->scalarPrototype()->end()
                                ->defaultValue(['*'])
                                ->treatNullLike(['*'])
                            ->end()

                            // Allowed HTTP methods for CORS requests
                            ->arrayNode('methods')
                                ->info('List of allowed HTTP methods for CORS requests.')
                                ->scalarPrototype()->end()
                                ->defaultValue(['GET', 'POST', 'OPTIONS'])
                                ->treatNullLike(['GET', 'POST', 'OPTIONS'])
                                // ->defaultValue(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'])
                                // ->treatNullLike(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'])
                            ->end()

                            // List of headers to expose in CORS requests
                            ->arrayNode('headers')
                                ->info('List of headers to expose in CORS requests.')
                                ->scalarPrototype()->end()
                                ->defaultValue([])
                            ->end()

                            // Headers to expose to the client
                            ->arrayNode('expose')
                                ->info('List of headers to expose to the client.')
                                ->scalarPrototype()->end()
                                ->defaultValue([])
                            ->end()

                            // Include credentials in CORS requests
                            ->booleanNode('credentials')
                                ->info('If true, includes credentials in CORS requests.')
                                ->defaultFalse()
                                ->treatNullLike(false)
                            ->end()

                            // Max age for CORS preflight requests
                            ->integerNode('max_age')
                                ->info('Maximum age for CORS preflight requests.')
                                ->defaultValue(3600)
                                ->treatNullLike(3600)
                            ->end()

                        ->end()
                    ->end()

                    // Behavior settings
                    ->arrayNode('behavior')
                        ->info('Behavior settings for API responses.')
                        ->addDefaultsIfNotSet()->children()

                            // Strip "X-" prefix from headers
                            ->booleanNode('strip_x_prefix')
                                ->info('If true, strips "X-" prefix from headers when exposing them.')
                                ->defaultFalse()
                                ->treatNullLike(false)
                            ->end()

                            // Keep "X-" prefix in headers
                            ->booleanNode('keep_legacy')
                                ->info('If true, keeps "X-" prefix in headers when exposing them.')
                                ->defaultFalse()
                                ->treatNullLike(false)
                            ->end()

                        ->end()
                    ->end()

                    // Headers directives
                    ->arrayNode('headers')
                        ->info('List of headers to expose in CORS requests.')
                        ->variablePrototype()->end()
                        ->defaultValue([])
                    ->end()

                    // Compression settings
                    ->arrayNode('compression')
                        ->info('Configuration for response compression settings.')
                        ->addDefaultsIfNotSet()->children()

                            // Enable or disable response compression
                            ->booleanNode('enabled')
                                ->info('Enable or disable response compression.')
                                ->defaultFalse()
                                ->treatNullLike(false)
                            ->end()

                            // Compression format to use
                            ->enumNode('format')
                                ->info('Compression format to use.')
                                ->defaultValue('gzip')
                                ->values(['gzip', 'deflate', 'brotli'])
                                ->treatNullLike('gzip')
                            ->end()

                            // Compression level (0-9) for the selected format
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

                    // Serialization groups
                    ->arrayNode('ignore')
                        ->info('List of attributes to exclude from the response.')
                        ->scalarPrototype()->end()
                        ->defaultValue(['password', 'secret'])
                        ->treatNullLike(['password', 'secret'])
                    ->end()

                    // Datetime formatting
                    ->arrayNode('datetime')
                        ->info('Controls how datetime objects are formatted during serialization.')
                        ->addDefaultsIfNotSet()
                        ->children()

                            // Date/time output format
                            ->scalarNode('format')
                                ->info('Date/time output format (e.g. "Y-m-d H:i:s" or ISO 8601). Set to null to use Symfony’s default format.')
                                ->defaultValue('Y-m-d H:i:s')
                                ->treatNullLike('Y-m-d H:i:s')
                            ->end()

                            // Timezone for date/time serialization
                            ->scalarNode('timezone')
                                ->info('Timezone applied when serializing datetime values (e.g. "UTC", "Europe/Paris"). Set to null to use the system default.')
                                ->defaultValue('UTC')
                                ->treatNullLike('UTC')
                            ->end()

                        ->end()
                    ->end()

                    // Skip null values in serialization
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

                    // Merge strategy for access control settings
                    ->enumNode('merge')
                        ->info('Defines how to handle merging access control settings: "replace" to overwrite existing settings, "append" to add to them, or "prepend" to add them at the beginning.')
                        ->values(MergeStrategy::toArray(true))
                        ->defaultValue(MergeStrategy::APPEND->value)
                        ->treatNullLike(MergeStrategy::APPEND->value)
                    ->end()

                    // Required roles for accessing this API provider
                    ->arrayNode('roles')
                        ->info('List of Symfony security roles required to access this API provider.')
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()

                    // Custom voter for access control
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
                            ->treatNullLike(true)
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
                        // Deprecation
                        // ──────────────────────────────
                        ->arrayNode('deprecation')
                            ->info('API deprecation notices for this authentication provider.')
                            ->addDefaultsIfNotSet()->children()

                                // Enable or disable deprecation
                                ->booleanNode('enabled')
                                    ->info('Enable or disable the deprecation for this authentication provider.')
                                    ->defaultNull()
                                    ->treatNullLike(false)
                                ->end()

                                // Deprecation start date
                                ->scalarNode('start_at')
                                    ->info('Deprecation since date')
                                    ->defaultNull()
                                ->end()

                                // Deprecation sunset date
                                ->scalarNode('sunset_at')
                                    ->info('Deprecation sunset date')
                                    ->defaultNull()
                                ->end()

                                // Deprecation link
                                ->scalarNode('link')
                                    ->info('Deprecation link')
                                    ->defaultNull()
                                ->end()

                                // Deprecation successor link
                                ->scalarNode('successor')
                                    ->info('Deprecation successor link')
                                    ->defaultNull()
                                ->end()

                                // Deprecation message
                                ->scalarNode('message')
                                    ->info('Deprecation message')
                                    ->defaultNull()
                                ->end()

                            ->end()
                        ->end()

                        // ──────────────────────────────
                        // Route
                        // ──────────────────────────────
                        ->arrayNode('routes')
                            ->info('Override default route name or URL prefix for security-related endpoints.')
                            ->addDefaultsIfNotSet()->children()

                                // Custom route name pattern
                                ->scalarNode('pattern')
                                    ->info('Custom route name pattern. Falls back to global `routes.name` if null.')
                                    ->defaultNull()
                                ->end()

                                // Route prefix for security-related endpoints (login, registration, etc.)
                                ->scalarNode('prefix')
                                    ->info('Route prefix for security-related endpoints (login, registration, etc.).')
                                    ->defaultNull()
                                ->end()

                                // Additional route prefix for security-related endpoints
                                ->scalarNode('additional_prefix')
                                    ->info('Route prefix for security-related endpoints (login, registration, etc.).')
                                    ->defaultValue('auth')
                                    ->treatNullLike('auth')
                                ->end()

                                // Configure specific hosts for the endpoint routes
                                ->arrayNode('hosts')
                                    ->info('Configure specific hosts for the endpoint routes.')
                                    ->normalizeKeys(false)
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()

                                // Configure specific schemes for the endpoint routes
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

                                // Enable or disable URL support in API responses
                                ->booleanNode('support')
                                    ->info('Whether to include URL elements in API responses.')
                                    ->defaultNull()
                                ->end()

                                // Generate absolute URLs if true, relative otherwise
                                ->booleanNode('absolute')
                                    ->info('Generate absolute URLs if true, relative otherwise')
                                    ->defaultNull()
                                ->end()

                                // The name of the URL property in response
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

                                // Account template path
                                ->scalarNode('account')
                                    ->info('Path to the response template file used as a model for formatting the API output for account operations.')
                                    ->defaultNull()
                                ->end()

                                // Delete template path
                                ->scalarNode('delete')
                                    ->info('Path to the response template file used as a model for formatting the API output for delete operations.')
                                    ->defaultNull()
                                ->end()

                                // Error template path
                                ->scalarNode('error')
                                    ->info('Path to the response template file used as a model for formatting error responses.')
                                    ->defaultNull()
                                ->end()

                                // Not found template path
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

                                // Serialization groups
                                ->arrayNode('groups')
                                    ->info('List of Symfony serialization groups to apply when serializing the response for this endpoint.')
                                    ->normalizeKeys(false)
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()

                                // Attributes or properties to ignore during serialization
                                ->arrayNode('ignore')
                                    ->info('List of entity attributes or properties to explicitly exclude from serialization.')
                                    ->normalizeKeys(false)
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()

                                // Transformer or DTO class
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
                        // Endpoints
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

                                                // Custom route name
                                                ->scalarNode('name')
                                                    ->info('Route name. If not defined, it will be generated automatically based on the collection and endpoint name.')
                                                    ->defaultNull()
                                                ->end()

                                                // Custom route path
                                                ->scalarNode('path')
                                                    ->info('Optional custom path for this endpoint.')
                                                    ->defaultNull()
                                                ->end()

                                                // Allowed HTTP methods
                                                ->arrayNode('methods')
                                                    ->info('Allowed HTTP methods. Must be explicitly defined to avoid accidental exposure.')
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([Request::METHOD_POST])
                                                    ->treatNullLike([Request::METHOD_POST])
                                                ->end()

                                                // Optional Symfony controller (FQCN::method)
                                                ->scalarNode('controller')
                                                    ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                    ->defaultValue('OSW3\Api\Controller\Auth\RegisterController::register')
                                                    ->treatNullLike('OSW3\Api\Controller\Auth\RegisterController::register')
                                                    ->validate()
                                                        ->IfTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                        ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                    ->end()
                                                ->end()

                                                // Advanced route options
                                                ->arrayNode('options')
                                                    ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
                                                ->end()

                                                // Hosts configuration
                                                ->arrayNode('hosts')
                                                    ->info('Configure specific hosts for the endpoint routes.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
                                                ->end()

                                                // Schemes configuration
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

                                                // Identifier field
                                                ->scalarNode('identifier')
                                                    ->info('Identifier field for the User entity (e.g., "email").')
                                                    ->defaultValue('email')
                                                    ->treatNullLike('email')
                                                ->end()

                                                // Password field
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
                                                
                                                // Custom route name pattern
                                                ->scalarNode('name')
                                                    ->info('Route name. If not defined, it will be generated automatically based on the collection and endpoint name.')
                                                    ->defaultNull()
                                                ->end()
                                                
                                                // Custom route path
                                                ->scalarNode('path')
                                                    ->info('Optional custom path for this endpoint.')
                                                    ->defaultNull()
                                                ->end()
                                                
                                                // Allowed HTTP methods
                                                ->arrayNode('methods')
                                                    ->info('Allowed HTTP methods. Must be explicitly defined to avoid accidental exposure.')
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([Request::METHOD_POST])
                                                    ->treatNullLike([Request::METHOD_POST])
                                                ->end()
                                                
                                                // Optional Symfony controller (FQCN::method)
                                                ->scalarNode('controller')
                                                    ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                    ->defaultValue('OSW3\Api\Controller\Auth\LoginController::login')
                                                    ->treatNullLike('OSW3\Api\Controller\Auth\LoginController::login')
                                                    ->validate()
                                                        ->IfTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                        ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                    ->end()
                                                ->end()
                                                
                                                // Advanced route options
                                                ->arrayNode('options')
                                                    ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
                                                ->end()
                                                
                                                // Hosts configuration
                                                ->arrayNode('hosts')
                                                    ->info('Configure specific hosts for the endpoint routes.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
                                                ->end()
                                                
                                                // Schemes configuration
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

                                                // Custom route name pattern
                                                ->scalarNode('name')
                                                    ->info('Route name. If not defined, it will be generated automatically based on the collection and endpoint name.')
                                                    ->defaultNull()
                                                ->end()

                                                // Custom route path
                                                ->scalarNode('path')
                                                    ->info('Optional custom path for this endpoint.')
                                                    ->defaultNull()
                                                ->end()

                                                // Allowed HTTP methods
                                                ->arrayNode('methods')
                                                    ->info('Allowed HTTP methods. Must be explicitly defined to avoid accidental exposure.')
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([Request::METHOD_POST])
                                                    ->treatNullLike([Request::METHOD_POST])
                                                ->end()

                                                // Optional Symfony controller (FQCN::method)
                                                ->scalarNode('controller')
                                                    ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                    ->defaultValue('OSW3\Api\Controller\Auth\LogoutController::logout')
                                                    ->treatNullLike('OSW3\Api\Controller\Auth\LogoutController::logout')
                                                    ->validate()
                                                        ->IfTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                        ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                    ->end()
                                                ->end()

                                                // Advanced route options
                                                ->arrayNode('options')
                                                    ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
                                                ->end()

                                                // Hosts configuration
                                                ->arrayNode('hosts')
                                                    ->info('Configure specific hosts for the endpoint routes.')
                                                    ->normalizeKeys(false)
                                                    ->scalarPrototype()->end()
                                                    ->defaultValue([])
                                                ->end()

                                                // Schemes configuration
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
                                                        ->IfTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
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
                                                        ->IfTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
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
                                                        ->IfTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
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
                                                        ->IfTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
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
                                                        ->IfTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
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
                                                        ->IfTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
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
                                                        ->IfTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
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
                                                        ->IfTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
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
                                                        ->IfTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
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
                                                        ->IfTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
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
                                                        ->IfTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
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
                                                        ->IfTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
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
                            ->treatNullLike(true)
                        ->end()

                        // ──────────────────────────────
                        // Collection name
                        // ──────────────────────────────
                        ->scalarNode('name')
                            ->info('Collection name in URLs and route names. Auto-generated from entity if null (e.g. App\\Entity\\Book → books).')
                            ->defaultNull()
                        ->end()

                        // ──────────────────────────────
                        // Deprecation
                        // ──────────────────────────────
                        ->arrayNode('deprecation')
                            ->info('API deprecation notices for this collection.')
                            ->addDefaultsIfNotSet()->children()

                                // Deprecation enabled
                                ->booleanNode('enabled')
                                    ->info('Enable or disable the deprecation for this collection.')
                                    ->defaultNull()
                                    ->treatNullLike(false)
                                ->end()

                                // Deprecation start date
                                ->scalarNode('start_at')
                                    ->info('Deprecation since date')
                                    ->defaultNull()
                                ->end()

                                // Deprecation removal date
                                ->scalarNode('sunset_at')
                                    ->info('Deprecation sunset date')
                                    ->defaultNull()
                                ->end()

                                // Deprecation link
                                ->scalarNode('link')
                                    ->info('Deprecation link')
                                    ->defaultNull()
                                ->end()

                                // Deprecation successor link
                                ->scalarNode('successor')
                                    ->info('Deprecation successor link')
                                    ->defaultNull()
                                ->end()

                                // Deprecation message
                                ->scalarNode('message')
                                    ->info('Deprecation message')
                                    ->defaultNull()
                                ->end()

                            ->end()
                        ->end()

                        // ──────────────────────────────
                        // Route
                        // ──────────────────────────────
                        ->arrayNode('routes')
                            ->info('Override default route name or URL prefix for this specific collection.')
                            ->addDefaultsIfNotSet()->children()

                                // Custom route name pattern
                                ->scalarNode('pattern')
                                    ->info('Custom route name pattern. Falls back to global `routes.name` if null.')
                                    ->defaultNull()
                                ->end()

                                // Route prefix for security-related endpoints (login, registration, etc.)
                                ->scalarNode('prefix')
                                    ->info('Custom URL prefix. Falls back to global `routes.prefix` if null.')
                                    ->defaultNull()
                                ->end()

                                // Configure specific hosts for the endpoint routes
                                ->arrayNode('hosts')
                                    ->info('Configure specific hosts for the collection routes.')
                                    ->normalizeKeys(false)
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()

                                // Configure specific schemes for the endpoint routes
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
                            
                                // Is pagination enabled
                                ->variableNode('enabled')
                                    ->info('Enable or disable pagination. If null, inherits from parent.')
                                    ->validate()
                                        ->ifTrue(fn($v) => !in_array($v, [true, false, null], true))
                                        ->thenInvalid('The "enabled" value must be true, false, or null.')
                                    ->end()
                                ->end()

                                // Limit the number of items per page
                                ->integerNode('limit')
                                    ->info('Limit the number of items per page for this collection.')
                                    ->defaultValue(10)
                                    ->treatNullLike(-1)
                                ->end()

                                // Max limit of items per page
                                ->integerNode('max_limit')
                                    ->info('Max number of items per page for this collection.')
                                    ->defaultValue(100)
                                    ->treatNullLike(-1)
                                ->end()

                                // Allow overriding the "limit" parameter via URL
                                ->variableNode('allow_limit_override')
                                    ->info('Allow overriding the "limit" parameter via URL (e.g. ?limit=50) for this collection.')
                                    ->validate()
                                        ->ifTrue(fn($v) => !in_array($v, [true, false, null], true))
                                        ->thenInvalid('The "enabled" value must be true, false, or null.')
                                    ->end()
                                ->end()

                            ->end()
                        ->end()

                        // ──────────────────────────────
                        // URL support
                        // ──────────────────────────────
                        ->arrayNode('url')
                            ->info('URL Support (in response) for this collection.')
                            ->addDefaultsIfNotSet()->children()

                                // Enable or disable URL support in API responses
                                ->booleanNode('support')
                                    ->info('Whether to include URL elements in API responses.')
                                    ->defaultNull()
                                ->end()

                                // Generate absolute URLs if true, relative otherwise
                                ->booleanNode('absolute')
                                    ->info('Generate absolute URLs if true, relative otherwise')
                                    ->defaultNull()
                                ->end()

                                // The name of the URL property in response
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

                                // Enable or disable rate limiting
                                ->variableNode('enabled')
                                    ->info('Enable or disable rate limiting for this API provider.')
                                    ->validate()
                                        ->ifTrue(fn($v) => !in_array($v, [true, false, null], true))
                                        ->thenInvalid('The "enabled" value must be true, false, or null.')
                                    ->end()
                                ->end()

                                // Global rate limit
                                ->scalarNode('limit')
                                    ->info('Maximum number of requests allowed in the specified time window.')
                                    ->defaultValue('100/hour')
                                ->end()

                                // Specific rate limits based on user roles
                                ->arrayNode('by_role')
                                    ->info('Specific rate limits based on user roles.')
                                    ->normalizeKeys(false)
                                    // ->scalarPrototype()->end()
                                    // ->defaultValue([])
                                ->end()

                                // Specific rate limits for individual users
                                ->arrayNode('by_user')
                                    ->info('Specific rate limits for individual users identified by user ID or username.')
                                    ->normalizeKeys(false)
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()

                                // Specific rate limits based on client IP addresses
                                ->arrayNode('by_ip')
                                    ->info('Specific rate limits based on client IP addresses.')
                                    ->normalizeKeys(false)
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()

                                // Specific rate limits for different application keys or API clients
                                ->arrayNode('by_application')
                                    ->info('Specific rate limits for different application keys or API clients.')
                                    ->normalizeKeys(false)
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()

                                // Include rate limit headers in responses
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

                                // List template path
                                ->scalarNode('list')
                                    ->info('Path to the response template file used as a model for formatting the API output for lists.')
                                    ->defaultNull()
                                ->end()

                                // Single template path
                                ->scalarNode('single')
                                    ->info('Path to the response template file used as a model for formatting the API output for single items.')
                                    ->defaultNull()
                                ->end()

                                // Delete template path
                                ->scalarNode('delete')
                                    ->info('Path to the response template file used as a model for formatting the API output for delete operations.')
                                    ->defaultNull()
                                ->end()

                                // Error template path
                                ->scalarNode('error')
                                    ->info('Path to the response template file used as a model for formatting error responses.')
                                    ->defaultNull()
                                ->end()

                                // Not found template path
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
                                    ->normalizeKeys(false)
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()

                                ->arrayNode('ignore')
                                    ->info('List of entity attributes or properties to explicitly exclude from serialization.')
                                    ->normalizeKeys(false)
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()

                                ->scalarNode('transformer')
                                    ->info('Optional class FQCN of a transformer or DTO to convert the entity data before serialization.')
                                    ->defaultNull()
                                    ->validate()
                                        ->IfTrue(fn($v) => !TransformerValidator::isValid($v))
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

                                // Merge strategy for access control settings
                                ->enumNode('merge')
                                    ->info('Defines how to handle merging access control settings: "replace" to overwrite existing settings, "append" to add to them, or "prepend" to add them at the beginning.')
                                    ->values(array_merge(MergeStrategy::toArray(true), [null]))
                                    ->defaultNull()
                                ->end()

                                // Required roles for accessing this API collection
                                ->arrayNode('roles')
                                    ->info('List of Symfony security roles required to access this collection.')
                                    ->scalarPrototype()->end()
                                    ->defaultValue([])
                                ->end()

                                // Custom voter for access control
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
                                        ->treatNullLike(true)
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

                                            // Custom route name pattern
                                            ->scalarNode('pattern')
                                                ->info('Custom route name pattern. Falls back to global `routes.name` if null.')
                                                ->defaultNull()
                                            ->end()

                                            // Custom route name
                                            ->scalarNode('name')
                                                ->info('Route name. If not defined, it will be generated automatically based on the collection and endpoint name.')
                                                ->defaultNull()
                                            ->end()

                                            // Custom route path
                                            ->scalarNode('path')
                                                ->info('Optional custom path for this endpoint.')
                                                ->defaultNull()
                                            ->end()

                                            // Allowed HTTP methods
                                            ->arrayNode('methods')
                                                ->info('Allowed HTTP methods. Must be explicitly defined to avoid accidental exposure.')
                                                ->scalarPrototype()->end()
                                            ->end()

                                            // Controller
                                            ->scalarNode('controller')
                                                ->info('Optional Symfony controller (FQCN::method). If not defined, the endpoint will automatically use the configured "repository.method" to fetch and expose data.')
                                                ->defaultNull()
                                                ->validate()
                                                    ->IfTrue(fn($controller) => $controller !== null && !ControllerValidator::isValid($controller))
                                                    ->thenInvalid('The specified controller "%s" does not exist or the method is not callable.')
                                                ->end()
                                            ->end()

                                            // Requirements
                                            ->arrayNode('requirements')
                                                ->info('Regex constraints for dynamic route parameters. Keys are parameter names, values are regular expressions that must be matched. For example: {id} must be digits, {slug} must be lowercase letters and dashes.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            // Advanced route options
                                            ->arrayNode('options')
                                                ->info('Advanced route options used by the Symfony router. Common keys include "utf8" (true to support UTF-8 paths), "compiler_class" (custom RouteCompiler), or any custom metadata for route generation and matching.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            // Condition expression
                                            ->scalarNode('condition')
                                                ->info('Optional condition expression for the route.')
                                                ->defaultNull()
                                            ->end()

                                            // Allowed hosts
                                            ->arrayNode('hosts')
                                                ->info('Configure specific hosts for the endpoint routes.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            // Allowed schemes
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
                            
                                            // Is pagination enabled
                                            ->variableNode('enabled')
                                                ->info('Enable or disable pagination. If null, inherits from parent.')
                                                ->validate()
                                                    ->ifTrue(fn($v) => !in_array($v, [true, false, null], true))
                                                    ->thenInvalid('The "enabled" value must be true, false, or null.')
                                                ->end()
                                            ->end()

                                            // Limit the number of items per page
                                            ->integerNode('limit')
                                                ->info('Limit the number of items per page for this endpoint.')
                                                ->defaultValue(10)
                                                ->treatNullLike(-1)
                                            ->end()

                                            // Max limit of items per page
                                            ->integerNode('max_limit')
                                                ->info('Max number of items per page for this endpoint.')
                                                ->defaultValue(100)
                                                ->treatNullLike(-1)
                                            ->end()

                                            // Allow overriding the "limit" parameter via URL
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

                                            // Enable or disable rate limiting
                                            ->variableNode('enabled')
                                                ->info('Enable or disable rate limiting for this API provider.')
                                                ->defaultNull()
                                                ->validate()
                                                    ->ifTrue(fn($v) => !in_array($v, [true, false, null], true))
                                                    ->thenInvalid('The "enabled" value must be true, false, or null.')
                                                ->end()
                                            ->end()

                                            // Global rate limit
                                            ->scalarNode('limit')
                                                ->info('Maximum number of requests allowed in the specified time window.')
                                                ->defaultValue('100/hour')
                                            ->end()

                                            // Specific rate limits based on user roles
                                            ->arrayNode('by_role')
                                                ->info('Specific rate limits based on user roles.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            // Specific rate limits for individual users
                                            ->arrayNode('by_user')
                                                ->info('Specific rate limits for individual users identified by user ID or username.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            // Specific rate limits based on client IP addresses
                                            ->arrayNode('by_ip')
                                                ->info('Specific rate limits based on client IP addresses.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            // Specific rate limits for different application keys or API clients
                                            ->arrayNode('by_application')
                                                ->info('Specific rate limits for different application keys or API clients.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            // Include rate limit headers in responses
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
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            ->arrayNode('ignore')
                                                ->info('List of entity attributes or properties to explicitly exclude from serialization.')
                                                ->normalizeKeys(false)
                                                ->scalarPrototype()->end()
                                                ->defaultValue([])
                                            ->end()

                                            ->scalarNode('transformer')
                                                ->info('Optional class FQCN of a transformer or DTO to convert the entity data before serialization.')
                                                ->defaultNull()
                                                ->validate()
                                                    ->IfTrue(fn($v) => !TransformerValidator::isValid($v))
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

                                            // Merge strategy for access control settings
                                            ->enumNode('merge')
                                                ->info('Defines how to handle merging access control settings: "replace" to overwrite existing settings, "append" to add to them, or "prepend" to add them at the beginning.')
                                                ->values(array_merge(MergeStrategy::toArray(true), [null]))
                                                ->defaultNull()
                                            ->end()

                                            // Required roles for accessing this API endpoint
                                            ->arrayNode('roles')
                                                ->info('List of Symfony security roles required to access this endpoint, e.g., ["ROLE_ADMIN", "PUBLIC_ACCESS"].')
                                                ->scalarPrototype()->end()
                                                ->defaultValue(['PUBLIC_ACCESS'])
                                            ->end()

                                            // Custom voter for access control
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
                    ->IfTrue(fn($v) => EntityValidator::validateClassesExist(array_keys($v)))
                    ->thenInvalid('One or more entities defined in "api" do not exist. Check namespaces and spelling.')
                ->end()

            ->end() // of collections

        //     // ──────────────────────────────
        //     // Debug
        //     // ──────────────────────────────
		// 	->arrayNode('debug')
        //         ->info('Debug configuration')
        //         ->addDefaultsIfNotSet()->children()

        //             ->booleanNode('enabled')
        //                 ->info('Enable or disable debug.')
        //                 ->defaultFalse()
        //                 ->treatNullLike(false)
        //             ->end()

		// 	    ->end()
        //     ->end()

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
            // -> Provider level
            // -> Collections level
            // -> Endpoint level
            IsEnabledResolver::execute($providers);

            // Collections names (alias)
            // -> Collections level
            NameResolver::execute($providers);

            // Deprecation
            // -> Provider level
            // -> Collections level
            // -> Endpoint level
            DeprecationResolver::execute($providers);

            // API Resolver (API Versioning)
            // -> Provider level
            ApiResolver::execute($providers);

            // Route 
            // -> Provider level
            // -> Collections level
            // -> Endpoint level
            RouteResolver::execute($providers);

            // Pagination
            // -> Provider level
            // -> Collections level
            // -> Endpoint level
            PaginationResolver::execute($providers);

            // URL Support
            // -> Provider level
            // -> Collections level
            UrlSupportResolver::execute($providers);

            // Rate limit
            // -> Provider level
            // -> Collections level (collections)
            // -> Endpoint level
            RateLimitResolver::execute($providers);

            // Templates paths
            // -> Provider level
            // -> Collections level
            // -> Endpoint level
            TemplatesResolver::execute($providers);

            // Response

            // Serialization
            // -> Provider level
            // -> Collections level
            // -> Endpoint level
            SerializationResolver::execute($providers);

            // Access Control
            // -> Provider level
            // -> Collections level
            // -> Endpoint level
            AccessControlResolver::execute($providers);

            return $providers;
        })
    ->end() // of Version generator
    ;
};