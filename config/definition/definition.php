<?php

use OSW3\Api\Enum\MimeType;
use OSW3\Api\Enum\Version\Mode;
use OSW3\Api\Enum\MergeStrategy;
use OSW3\Api\Resolver\ApiResolver;
use OSW3\Api\Enum\Version\Location;
use OSW3\Api\Resolver\NameResolver;
use OSW3\Api\Resolver\RouteResolver;
use OSW3\Api\Resolver\ResponseResolver;
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
    $definition->rootNode()->children()
    
    // ──────────────────────────────
    // Versioning
    // ──────────────────────────────
    ->arrayNode('versioning')
        ->info('API versioning configuration.')
        ->addDefaultsIfNotSet()->children()

            // Versioning mode
            // --
            // -> auto: automatically handle versioning based on defined versions
            // -> manual: manually specify versioning details
            ->enumNode('mode')
                ->info('API versioning mode.')
                ->values(Mode::toArray(true))
                ->defaultValue(Mode::AUTO->value)
                ->treatNullLike(Mode::AUTO->value)
            ->end()

            // Version prefix
            // --
            // (e.g. "v" to have versions like v1, v2, etc.)
            ->scalarNode('prefix')
                ->info('API version prefix (e.g. "v").')
                ->defaultValue('v')
                ->treatNullLike('v')
            ->end()

            // Version location
            // --
            // How the version is exposed: in URL path, HTTP header, query parameter, or subdomain
            // -> path: /api/v1/resource
            // -> header: X-API-Version: 1
            // -> param: ?version=1
            // -> subdomain: v1.api.example.com
            ->enumNode('location')
                ->info('How the version is exposed: in URL path, HTTP header, query parameter, or subdomain.')
                ->values(Location::toArray(true))
                ->defaultValue(Location::PATH->value)
                ->treatNullLike(Location::PATH->value)
            ->end()

        ->end()
    ->end() // of versioning

    // ──────────────────────────────
    // Pagination
    // ──────────────────────────────
    ->arrayNode('pagination')
        ->info('API pagination configuration.')
        ->addDefaultsIfNotSet()->children()

            // Enable or disable pagination globally
            // --
            // If true, pagination is enabled for all collections by default
            ->booleanNode('enabled')
                ->info('Enable or disable pagination globally.')
                ->defaultTrue()
                ->treatNullLike(true)
            ->end()

            // Default number of items returned per page
            // --
            // Sets the default limit for paginated responses
            ->integerNode('default_limit')
                ->info('Default number of items returned per page.')
                ->defaultValue(10)
                ->treatNullLike(10)
            ->end()

            // Maximum number of items returned per page
            // --
            // Sets the maximum limit for paginated responses
            // helps prevent performance issues from excessively large responses
            // -> if a client requests a limit higher than this, it will be capped to this value
            // -> if default_limit is higher than max_limit, default_limit will be used
            // -> if set to 0, there is no maximum limit
            ->integerNode('max_limit')
                ->info('Maximum number of items returned per page.')
                ->defaultValue(100)
                ->treatNullLike(100)
            ->end()

            // Allow limit override via URL parameter
            // --
            // If true, clients can override the default limit by specifying a "limit" parameter in the URL
            // (e.g. ?limit=50)
            ->booleanNode('allow_limit_override')
                ->info('Allow limit override via URL parameter.')
                ->defaultTrue()
                ->treatNullLike(true)
            ->end()

            // Pagination parameter names
            // --
            // Defines the names of the query parameters used for pagination
            // -> page: the page number parameter
            // -> limit: the number of items per page parameter
            ->arrayNode('parameters')
                ->info('Pagination parameter names.')
                ->addDefaultsIfNotSet()->children()

                    // Page parameter name
                    ->scalarNode('page')
                        ->info('Page parameter name.')
                        ->defaultValue('page')
                        ->treatNullLike('page')
                    ->end()

                    // Limit parameter name
                    ->scalarNode('limit')
                        ->info('Limit parameter name.')
                        ->defaultValue('limit')
                        ->treatNullLike('limit')
                    ->end()

                ->end()
            ->end()

        ->end()
    ->end() // of pagination

    // ──────────────────────────────
    // Support URL
    // ──────────────────────────────
    ->arrayNode('url_support')
        ->info('API support URL configuration.')
        ->addDefaultsIfNotSet()->children()

            // Support URLs in response
            // --
            // If true, includes URL elements in API responses
            ->booleanNode('enabled')
                ->info('Whether to include URL elements in API responses.')
                ->defaultTrue()
                ->treatNullLike(true)
            ->end()

            // Absolute URLs
            // --
            // If true, generates absolute URLs; otherwise, generates relative URLs
            ->booleanNode('absolute')
                ->info('Generate absolute URLs if true, relative otherwise')
                ->defaultTrue()
                ->treatNullLike(true)
            ->end()

            // URL property name
            // --
            // The name of the property in the response that holds the URL
            ->scalarNode('property')
                ->info('The name of the URL property in response.')
                ->defaultValue('url')
                ->treatNullLike('url')
            ->end()

        ->end()
    ->end() // of url_support

    // ──────────────────────────────
    // Templates
    // ──────────────────────────────
    ->arrayNode('templates')
        // Define template paths for various response types
        // --
        // Each template defines the structure of the API response for different scenarios
        // e.g., list responses, single item responses, error responses, etc.
        // Paths are relative to the bundle directory or can be absolute paths
        // Default templates are provided, but can be overridden here
        ->info('API templates configuration.')
        ->addDefaultsIfNotSet()->children()
        
            // -- Error templates --

            // General error template
            ->scalarNode('error')
                ->info('Path to the response template file used as a model for formatting error responses.')
                ->defaultValue('Resources/templates/yaml/error.yaml')
                ->treatNullLike('Resources/templates/yaml/error.yaml')
            ->end()
            
            // 400 Bad Request
            ->scalarNode('error_400')
                ->info('Path to the response template file used as a model for formatting bad request responses (e.g. 400 Bad Request).')
                ->defaultValue('Resources/templates/yaml/error_400.yaml')
                ->treatNullLike('Resources/templates/yaml/error_400.yaml')
            ->end()
            
            // 401 Unauthorized
            ->scalarNode('error_401')
                ->info('Path to the response template file used as a model for formatting unauthorized responses (e.g. 401 Unauthorized).')
                ->defaultValue('Resources/templates/yaml/error_401.yaml')
                ->treatNullLike('Resources/templates/yaml/error_401.yaml')
            ->end()
            
            // 403 Forbidden
            ->scalarNode('error_403')
                ->info('Path to the response template file used as a model for formatting forbidden responses (e.g. 403 Forbidden).')
                ->defaultValue('Resources/templates/yaml/error_403.yaml')
                ->treatNullLike('Resources/templates/yaml/error_403.yaml')
            ->end()
            
            // 404 Not Found
            ->scalarNode('error_404')
                ->info('Path to the response template file used as a model for formatting not found responses (e.g. 404 Not Found).')
                ->defaultValue('Resources/templates/yaml/error_404.yaml')
                ->treatNullLike('Resources/templates/yaml/error_404.yaml')
            ->end()

            // 405 Method Not Allowed
            ->scalarNode('error_405')
                ->info('Path to the response template file used as a model for formatting method not allowed responses (e.g. 405 Method Not Allowed).')
                ->defaultValue('Resources/templates/yaml/error_405.yaml')
                ->treatNullLike('Resources/templates/yaml/error_405.yaml')
            ->end()

            // 409 Conflict
            ->scalarNode('error_409')
                ->info('Path to the response template file used as a model for formatting conflict responses (e.g. 409 Conflict).')
                ->defaultValue('Resources/templates/yaml/error_409.yaml')
                ->treatNullLike('Resources/templates/yaml/error_409.yaml')
            ->end()

            // 422 Unprocessable Entity
            ->scalarNode('error_422')
                ->info('Path to the response template file used as a model for formatting unprocessable entity responses (e.g. 422 Unprocessable Entity).')
                ->defaultValue('Resources/templates/yaml/error_422.yaml')
                ->treatNullLike('Resources/templates/yaml/error_422.yaml')
            ->end()

            // 429 Too Many Requests
            ->scalarNode('error_429')
                ->info('Path to the response template file used as a model for formatting too many requests responses (e.g. 429 Too Many Requests).')
                ->defaultValue('Resources/templates/yaml/error_429.yaml')
                ->treatNullLike('Resources/templates/yaml/error_429.yaml')
            ->end()

            // 500 Internal Server Error
            ->scalarNode('error_500')
                ->info('Path to the response template file used as a model for formatting internal server error responses (e.g. 500 Internal Server Error).')
                ->defaultValue('Resources/templates/yaml/error_500.yaml')
                ->treatNullLike('Resources/templates/yaml/error_500.yaml')
            ->end()

            // 502 Bad Gateway
            ->scalarNode('error_502')
                ->info('Path to the response template file used as a model for formatting bad gateway responses (e.g. 502 Bad Gateway).')
                ->defaultValue('Resources/templates/yaml/error_502.yaml')
                ->treatNullLike('Resources/templates/yaml/error_502.yaml')
            ->end()

            // 503 Service Unavailable
            ->scalarNode('error_503')
                ->info('Path to the response template file used as a model for formatting service unavailable responses (e.g. 503 Service Unavailable).')
                ->defaultValue('Resources/templates/yaml/error_503.yaml')
                ->treatNullLike('Resources/templates/yaml/error_503.yaml')
            ->end()

            // 504 Gateway Timeout
            ->scalarNode('error_504')
                ->info('Path to the response template file used as a model for formatting gateway timeout responses (e.g. 504 Gateway Timeout).')
                ->defaultValue('Resources/templates/yaml/error_504.yaml')
                ->treatNullLike('Resources/templates/yaml/error_504.yaml')
            ->end()


            // -- Auth templates --

            // Auth: Login
            ->scalarNode('login')
                ->info('Path to the response template file used as a model for formatting login responses.')
                ->defaultValue('Resources/templates/yaml/auth/login.yaml')
                ->treatNullLike('Resources/templates/yaml/auth/login.yaml')
            ->end()

            // Auth: Logout
            ->scalarNode('logout')
                ->info('Path to the response template file used as a model for formatting logout responses.')
                ->defaultValue('Resources/templates/yaml/auth/logout.yaml')
                ->treatNullLike('Resources/templates/yaml/auth/logout.yaml')
            ->end()

            // Auth: Refresh Token
            ->scalarNode('refresh_token')
                ->info('Path to the response template file used as a model for formatting refresh token responses.')
                ->defaultValue('Resources/templates/yaml/auth/refresh_token.yaml')
                ->treatNullLike('Resources/templates/yaml/auth/refresh_token.yaml')
            ->end()

            // Auth: Register
            ->scalarNode('register')
                ->info('Path to the response template file used as a model for formatting registration responses.')
                ->defaultValue('Resources/templates/yaml/auth/register.yaml')
                ->treatNullLike('Resources/templates/yaml/auth/register.yaml')
            ->end()

            // Auth: Me
            ->scalarNode('me')
                ->info('Path to the response template file used as a model for formatting "me" responses.')
                ->defaultValue('Resources/templates/yaml/auth/me.yaml')
                ->treatNullLike('Resources/templates/yaml/auth/me.yaml')
            ->end()

            // Auth: Account
            ->scalarNode('account')
                ->info('Path to the response template file used as a model for formatting account responses.')
                ->defaultValue('Resources/templates/yaml/auth/account.yaml')
                ->treatNullLike('Resources/templates/yaml/auth/account.yaml')
            ->end()

            // Auth: Update Password
            ->scalarNode('update_password')
                ->info('Path to the response template file used as a model for formatting update password responses.')
                ->defaultValue('Resources/templates/yaml/auth/update_password.yaml')
                ->treatNullLike('Resources/templates/yaml/auth/update_password.yaml')
            ->end()

            // Auth: Reset Password
            ->scalarNode('reset_password')
                ->info('Path to the response template file used as a model for formatting reset password responses.')
                ->defaultValue('Resources/templates/yaml/auth/reset_password.yaml')
                ->treatNullLike('Resources/templates/yaml/auth/reset_password.yaml')
            ->end()
                

            // -- Entities templates --
            
            // Entity: Empty
            ->scalarNode('empty')
                ->info('Path to the response template file used as a model for formatting empty responses.')
                ->defaultValue('Resources/templates/yaml/entities/empty.yaml')
                ->treatNullLike('Resources/templates/yaml/entities/empty.yaml')
            ->end()

            // Entity: List
            ->scalarNode('list')
                ->info('Path to the response template file used as a model for formatting entity list responses.')
                ->defaultValue('Resources/templates/yaml/entities/list.yaml')
                ->treatNullLike('Resources/templates/yaml/entities/list.yaml')
            ->end()
            
            // Entity: Single
            ->scalarNode('single')
                ->info('Path to the response template file used as a model for formatting single entity responses.')
                ->defaultValue('Resources/templates/yaml/entities/single.yaml')
                ->treatNullLike('Resources/templates/yaml/entities/single.yaml')
            ->end()

            // Entity: Created
            ->scalarNode('created')
                ->info('Path to the response template file used as a model for formatting entity creation responses.')
                ->defaultValue('Resources/templates/yaml/entities/created.yaml')
                ->treatNullLike('Resources/templates/yaml/entities/created.yaml')
            ->end()

            // Entity: Updated
            ->scalarNode('updated')
                ->info('Path to the response template file used as a model for formatting entity update responses.')
                ->defaultValue('Resources/templates/yaml/entities/updated.yaml')
                ->treatNullLike('Resources/templates/yaml/entities/updated.yaml')
            ->end()

            // Entity: Deleted
            ->scalarNode('deleted')
                ->info('Path to the response template file used as a model for formatting entity deletion responses.')
                ->defaultValue('Resources/templates/yaml/entities/deleted.yaml')
                ->treatNullLike('Resources/templates/yaml/entities/deleted.yaml')
            ->end()


            // -- System templates --

            // System: Health
            ->scalarNode('health')
                ->info('Path to the response template file used as a model for formatting system health check responses.')
                ->defaultValue('Resources/templates/yaml/system/health.yaml')
                ->treatNullLike('Resources/templates/yaml/system/health.yaml')
            ->end()

            // System: Maintenance
            ->scalarNode('maintenance')
                ->info('Path to the response template file used as a model for formatting system maintenance responses.')
                ->defaultValue('Resources/templates/yaml/system/maintenance.yaml')
                ->treatNullLike('Resources/templates/yaml/system/maintenance.yaml')
            ->end()

            // System: Rate_limit
            ->scalarNode('rate_limit')
                ->info('Path to the response template file used as a model for formatting system rate limit responses.')
                ->defaultValue('Resources/templates/yaml/system/rate_limit.yaml')
                ->treatNullLike('Resources/templates/yaml/system/rate_limit.yaml')
            ->end()

            
            // -- Files templates --

            // Files: Upload
            ->scalarNode('upload')
                ->info('Path to the response template file used as a model for formatting file upload responses.')
                ->defaultValue('Resources/templates/yaml/files/upload.yaml')
                ->treatNullLike('Resources/templates/yaml/files/upload.yaml')
            ->end()

            // Files: Download
            ->scalarNode('download')
                ->info('Path to the response template file used as a model for formatting file download responses.')
                ->defaultValue('Resources/templates/yaml/files/download.yaml')
                ->treatNullLike('Resources/templates/yaml/files/download.yaml')
            ->end()

        ->end()
    ->end() // of templates

    // ──────────────────────────────
    // Response
    // ──────────────────────────────
    ->arrayNode('response')
        ->info('API response configuration.')
        ->addDefaultsIfNotSet()->children()

            // Response format
            // --
            // Defines the format of the API responses (e.g. json, xml, yaml)
            ->arrayNode('format')
                ->info('API response format configuration.')
                ->addDefaultsIfNotSet()->children()

                    // Response format type
                    // --
                    // Specifies the format of the API responses
                    // Supported formats are defined in the MimeType enum
                    // Default is 'json'
                    // Possible values: json, xml, yaml, csv, etc.
                    ->enumNode('type')
                        ->info('Format of the API responses.')
                        ->values(array_keys(MimeType::toArray(true)))
                        ->defaultValue('json')
                        ->treatNullLike('json')
                        ->beforeNormalization()
                            ->ifString()
                            ->then(fn($v) => strtolower($v))
                        ->end()
                    ->end()

                    // MIME type override
                    // --
                    // If set, this value will be used as the Content-Type header.
                    ->scalarNode('mime_type')
                        ->info('MIME type override for the API responses. If set, this value will be used as the Content-Type header.')
                        ->defaultNull()
                    ->end()

                ->end()
            ->end()

            // Content negotiation
            // --
            // Settings for content negotiation in API responses
            ->arrayNode('content_negotiation')
                ->info('API content negotiation configuration.')
                ->addDefaultsIfNotSet()->children()

                    // Enable format override via URL parameter
                    // --
                    // If true, allows clients to override the response format by specifying a URL parameter
                    // (e.g. ?format=xml)
                    ->booleanNode('enabled')
                        ->info('Enable format override via URL parameter.')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()

                    // URL parameter name for format override
                    // --
                    // The name of the URL parameter that specifies the desired response format
                    // (e.g. "format" in ?format=xml)
                    ->scalarNode('parameter')
                        ->info('URL parameter name for format override.')
                        ->defaultValue('format')
                        ->treatNullLike('format')
                    ->end()

                ->end()
            ->end()

            // Pretty print JSON responses
            // --
            // If true, JSON responses will be pretty-printed for better readability
            ->arrayNode('pretty_print')
                ->info('Pretty print JSON responses for better readability.')
                ->addDefaultsIfNotSet()->children()

                    ->booleanNode('enabled')
                        ->info('Enable pretty printing for JSON responses.')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()

                ->end()
            ->end()

            // JSONP support
            // --
            // Settings for JSONP support in API responses
            ->arrayNode('jsonp')
                ->info('API JSONP support configuration.')
                ->addDefaultsIfNotSet()->children()

                    // Enable JSONP support
                    // --
                    // If true, enables JSONP support for API responses
                    ->booleanNode('enabled')
                        ->info('Enable JSONP support for API responses.')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()

                    // URL parameter name for JSONP callback
                    // --
                    // The name of the URL parameter that specifies the JSONP callback function
                    ->scalarNode('parameter')
                        ->info('URL parameter name for JSONP callback.')
                        ->defaultValue('callback')
                        ->treatNullLike('callback')
                    ->end()

                ->end()
            ->end()

            // Security settings
            // --
            // Settings to enhance the security of API responses
            ->arrayNode('security')
                ->info('Security settings for API responses.')
                ->addDefaultsIfNotSet()->children()

                    // Prevent JSON hijacking
                    // --
                    // If true, secures JSON responses against JSON hijacking attacks
                    ->arrayNode('hijacking_prevent')
                        ->info('Prevent JSON hijacking attacks.')
                        ->addDefaultsIfNotSet()->children()

                            ->booleanNode('enabled')
                                ->info('Enable JSON hijacking prevention.')
                                ->defaultTrue()
                                ->treatNullLike(true)
                            ->end()

                            // X-Frame-Options prefix
                            // --
                            // Prefix added to JSON responses to prevent hijacking
                            ->enumNode('x_frame_options')
                                ->info('Prefix added to JSON responses to prevent hijacking.')
                                ->values(['DENY', 'SAMEORIGIN', 'ALLOW-FROM'])
                                ->defaultValue("DENY")
                                ->treatNullLike("DENY")
                            ->end()
                        ->end()
                    ->end()

                    // Response checksum/hash settings
                    // --
                    // Settings for generating and verifying checksums/hashes for API responses
                    ->arrayNode('checksum')
                        ->info('Response checksum/hash settings.')
                        ->addDefaultsIfNotSet()->children()

                            // Enable checksum/hash verification
                            // --
                            // If true, enables checksum/hash verification for API responses
                            ->booleanNode('enabled')
                                ->info('Enable checksum/hash verification for API responses.')
                                ->defaultTrue()
                                ->treatNullLike(true)
                            ->end()

                            // Hash algorithm
                            // --
                            // Hash algorithm used for generating response checksums/hashes
                            ->enumNode('algorithm')
                                ->info('Hash algorithm used for generating response checksums/hashes.')
                                ->values(['sha1', 'sha256', 'sha512'])
                                ->defaultValue('sha256')
                                ->treatNullLike('sha256')
                            ->end()

                        ->end()
                    ->end()

                ->end()
            ->end()

            // Cache Control
            // --
            // Settings for cache control headers in API responses
            ->arrayNode('cache_control')
                ->info('API Cache-Control header configuration.')
                ->addDefaultsIfNotSet()->children()

                    // Enable Cache-Control headers
                    // --
                    // If true, adds Cache-Control headers to API responses
                    ->booleanNode('enabled')
                        ->info('Enable Cache-Control headers in API responses.')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()

                    // Public Cache-Control directive
                    // --
                    // If true, sets Cache-Control to "public", allowing shared caches. If false, sets to "private".
                    ->booleanNode('public')
                        ->info('If true, sets Cache-Control to "public". If false, sets to "private".')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()

                    // No store Cache-Control directive
                    // --
                    // If true, adds "no-store" to Cache-Control.
                    ->booleanNode('no_store')
                        ->info('If true, adds "no-store" to Cache-Control.')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()

                    // Must revalidate Cache-Control directive
                    // --
                    // If true, adds "must-revalidate" to Cache-Control.
                    ->booleanNode('must_revalidate')
                        ->info('If true, adds "must-revalidate" to Cache-Control.')
                        ->defaultTrue()
                        ->treatNullLike(true)
                    ->end()

                    // Max age for Cache-Control
                    // --
                    // Specifies the max-age directive in seconds for Cache-Control header
                    // (0 = no cache, higher values specify cache duration)
                    ->integerNode('max_age')
                        ->info('Max age in seconds (0 = no cache).')
                        ->defaultValue(3600)
                        ->treatNullLike(3600)
                        ->min(0)->max(31536000)
                    ->end()

                ->end()
            ->end()

            // CORS
            // --
            // Settings for CORS (Cross-Origin Resource Sharing) in API responses
            ->arrayNode('cors')
                ->info('API CORS (Cross-Origin Resource Sharing) configuration.')
                ->addDefaultsIfNotSet()->children()
                
                    // Enable CORS
                    // --
                    // If true, enables CORS support for API responses
                    ->booleanNode('enabled')
                        ->info('Enable CORS support for API responses.')
                        ->defaultTrue()
                        ->treatNullLike(true)
                    ->end()
                    
                    // Allowed origins
                    // --
                    // List of allowed origins for CORS
                    // See: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS#access-control-allow-origin
                    ->arrayNode('origins')
                        ->info('List of allowed origins for CORS.')
                        ->prototype('scalar')->end()
                        ->defaultValue(['*'])
                    ->end()
                    
                    // Allowed methods
                    // --
                    // List of allowed HTTP methods for CORS
                    // See: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS#access-control-allow-methods
                    ->arrayNode('methods')
                        ->info('List of allowed HTTP methods for CORS.')
                        ->prototype('scalar')->end()
                        ->defaultValue(['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'])
                    ->end()
                    
                    // Allowed headers
                    // --
                    // List of allowed HTTP headers for CORS
                    // See: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS#access-control-allow-headers
                    ->arrayNode('expose')
                        ->info('List of allowed HTTP headers for CORS.')
                        ->prototype('scalar')->end()
                        ->defaultValue(['Content-Type', 'Authorization'])
                    ->end()
                    
                    // Allow credentials
                    // --
                    // Indicates whether the response to the request can be exposed when the credentials flag is true.
                    // When used as part of a response to a preflight request, it indicates that the actual request can include user credentials.
                    // See: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS#access-control-allow-credentials
                    ->booleanNode('credentials')
                        ->info('Allow credentials in CORS requests.')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()

                    // Max age for preflight requests
                    // --
                    // Specifies how long the results of a preflight request can be cached
                    // See: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS#access-control-max-age
                    ->integerNode('max_age')
                        ->info('Max age in seconds for preflight requests.')
                        ->defaultValue(3600)
                        ->treatNullLike(3600)
                        ->min(0)->max(86400)
                    ->end()
                    
                ->end()
            ->end()

            // Compression
            // --
            // Settings for response compression in API responses
            // See: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Encoding
            ->arrayNode('compression')
                ->info('API response compression configuration.')
                ->addDefaultsIfNotSet()->children()

                    // Enable or disable response compression
                    // --
                    // If true, enables compression for API responses
                    ->booleanNode('enabled')
                        ->info('Enable or disable response compression.')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()

                    // Compression format to use
                    // --
                    // Specifies the compression format to use for API responses
                    // Supported formats: gzip, deflate, brotli
                    ->enumNode('format')
                        ->info('Compression format to use.')
                        ->defaultValue('gzip')
                        ->values(['gzip', 'deflate', 'brotli'])
                        ->treatNullLike('gzip')
                    ->end()

                    // Compression level (0-9) for the selected format
                    // --
                    // Specifies the compression level (0-9) for the selected compression format
                    // 0 = no compression, 9 = maximum compression
                    ->integerNode('level')
                        ->info('Compression level (0-9) for the selected format.')
                        ->defaultValue(6)
                        ->treatNullLike(6)
                        ->min(0)
                        ->max(9)
                    ->end()

                ->end()
            ->end()

            // Behavior
            // --
            // Settings to customize the behavior of API responses
            ->arrayNode('behavior')
                ->info('API response behavior configuration.')
                ->addDefaultsIfNotSet()->children()

                    // Strip "X-" prefix from headers
                    // --
                    // If true, strips "X-" prefix from headers when exposing them
                    ->booleanNode('strip_x_prefix')
                        ->info('If true, strips "X-" prefix from headers when exposing them.')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()

                    // Keep "X-" prefix in headers
                    // --
                    // If true, keeps "X-" prefix in headers for legacy support
                    ->booleanNode('keep_legacy')
                        ->info('If true, keeps "X-" prefix in headers for legacy support.')
                        ->defaultFalse()
                        ->treatNullLike(false)
                    ->end()

                ->end()
            ->end()

            // Headers
            // --
            // List of headers to expose in CORS requests
            //   !!!  Remplacer par 'expose' dans CORS ci-dessus  !!!
            // ->arrayNode('headers')
            //     ->info('List of headers to expose in CORS requests.')
            //     ->variablePrototype()->end()
            //     ->defaultValue([])
            // ->end()

        ->end()
    ->end() // of response

    // ──────────────────────────────
    // Serialization
    // ──────────────────────────────
    ->arrayNode('serialization')
        ->info('API serialization configuration.')
        ->addDefaultsIfNotSet()
        ->children()

            // Attributes to ignore during serialization
            // --
            // List of attribute names that should be excluded from the serialized response
            // (e.g. sensitive data like passwords)
            ->arrayNode('ignore')
                ->info('Attributes to ignore during serialization.')
                ->scalarPrototype()->end()
                ->defaultValue(['password', 'secret'])
                ->treatNullLike(['password', 'secret'])
            ->end()

            // Datetime formatting
            // --
            // Settings for formatting datetime objects during serialization
            // e.g. date format, timezone
            ->arrayNode('datetime')
                ->info('Datetime formatting settings for serialization.')
                ->addDefaultsIfNotSet()
                ->children()

                    // Date/time output format
                    // --
                    // Specifies the format used when serializing date/time values
                    // (e.g. "Y-m-d H:i:s" or ISO 8601)
                    ->scalarNode('format')
                        ->info('Format used when serializing date/time values.')
                        ->defaultValue('Y-m-d H:i:s')
                        ->treatNullLike('Y-m-d H:i:s')
                    ->end()

                    // Timezone for datetime serialization
                    // --
                    // Specifies the timezone applied when serializing datetime values
                    ->scalarNode('timezone')
                        ->info('Timezone applied when serializing datetime values.')
                        ->defaultValue('UTC')
                        ->treatNullLike('UTC')
                    ->end()

                ->end()
            ->end()

            // Skip null values in serialization
            // --
            // If true, fields with null values are omitted from the serialized response
            ->booleanNode('skip_null')
                ->info('Skip fields with null values during serialization.')
                ->defaultFalse()
                ->treatNullLike(false)
            ->end()

        ->end()
    ->end() // of serialization

    // ──────────────────────────────
    // Access Control
    // ──────────────────────────────
    ->arrayNode('access_control')
        ->info('API access control configuration.')
        ->addDefaultsIfNotSet()->children()

            // Merge strategy for access control settings
            // --
            // Defines how access control settings are merged with other configurations
            // (e.g. append, override)
            ->enumNode('merge')
                ->info('Strategy for merging access control settings.')
                ->values(MergeStrategy::toArray(true))
                ->defaultValue(MergeStrategy::APPEND->value)
                ->treatNullLike(MergeStrategy::APPEND->value)
            ->end()

            // Required roles for accessing this API provider
            // --
            // List of Symfony security roles required to access this API provider
            ->arrayNode('roles')
                ->info('Required roles for accessing this API provider.')
                ->scalarPrototype()->end()
                ->defaultValue([])
            ->end()

            // Custom voter for access control
            // --
            // Optional custom voter FQCN. If set, Symfony will use this voter to determine access instead of roles or expressions.
            ->scalarNode('voter')
                ->info('Custom voter FQCN for access control.')
                ->defaultNull()
            ->end()

        ->end()
    ->end() // of access_control

    // ──────────────────────────────
    // Version Providers (v1, v2…)
    // ──────────────────────────────
    ->arrayNode('providers')
        ->info('Each key is an API provider. Typically used to group routes, versions and settings.')
        ->useAttributeAsKey('version_provider')
        ->arrayPrototype()
        ->children()

            // ──────────────────────────────
            // Enabled
            // ──────────────────────────────
            ->booleanNode('enabled')
                ->info('Enable or disable this provider.')
                ->defaultTrue()
                ->treatNullLike(true)
            ->end() // of provider enabled

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
            ->end() // of provider deprecation

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
                        ->defaultNull()
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
            ->end() // of provider version
            
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
            ->end() // of provider routes

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
            ->end() // of provider pagination

            // ──────────────────────────────
            // URL support
            // ──────────────────────────────
			->arrayNode('url_support')
                ->info('URL Support (in response) for this API provider.')
                ->addDefaultsIfNotSet()->children()

                    // Support URLs in response
                    ->booleanNode('enabled')
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
            ->end() // of provider url support

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
            ->end() // of provider rate limit

            // ──────────────────────────────
            // Template
            // ──────────────────────────────
            ->arrayNode('templates')
                ->info('Paths to the response template files used as models for formatting the API output for lists and single items.')
                ->addDefaultsIfNotSet()->children()

                    // -- Error templates --

                    // General error template
                    ->scalarNode('error')
                        ->info('Path to the general response template file used as a model for formatting error responses.')
                        ->defaultNull()
                    ->end()
                    
                    // 400 Bad Request
                    ->scalarNode('error_400')
                        ->info('Path to the response template file used as a model for formatting bad request responses (e.g. 400 Bad Request).')
                        ->defaultNull()
                    ->end()
                    
                    // 401 Unauthorized
                    ->scalarNode('error_401')
                        ->info('Path to the response template file used as a model for formatting unauthorized responses (e.g. 401 Unauthorized).')
                        ->defaultNull()
                    ->end()
                    
                    // 403 Forbidden
                    ->scalarNode('error_403')
                        ->info('Path to the response template file used as a model for formatting forbidden responses (e.g. 403 Forbidden).')
                        ->defaultNull()
                    ->end()
                    
                    // 404 Not Found
                    ->scalarNode('error_404')
                        ->info('Path to the response template file used as a model for formatting not found responses (e.g. 404 Not Found).')
                        ->defaultNull()
                    ->end()

                    // 405 Method Not Allowed
                    ->scalarNode('error_405')
                        ->info('Path to the response template file used as a model for formatting method not allowed responses (e.g. 405 Method Not Allowed).')
                        ->defaultNull()
                    ->end()

                    // 409 Conflict
                    ->scalarNode('error_409')
                        ->info('Path to the response template file used as a model for formatting conflict responses (e.g. 409 Conflict).')
                        ->defaultNull()
                    ->end()

                    // 422 Unprocessable Entity
                    ->scalarNode('error_422')
                        ->info('Path to the response template file used as a model for formatting unprocessable entity responses (e.g. 422 Unprocessable Entity).')
                        ->defaultNull()
                    ->end()

                    // 429 Too Many Requests
                    ->scalarNode('error_429')
                        ->info('Path to the response template file used as a model for formatting too many requests responses (e.g. 429 Too Many Requests).')
                        ->defaultNull()
                    ->end()

                    // 500 Internal Server Error
                    ->scalarNode('error_500')
                        ->info('Path to the response template file used as a model for formatting internal server error responses (e.g. 500 Internal Server Error).')
                        ->defaultNull()
                    ->end()

                    // 502 Bad Gateway
                    ->scalarNode('error_502')
                        ->info('Path to the response template file used as a model for formatting bad gateway responses (e.g. 502 Bad Gateway).')
                        ->defaultNull()
                    ->end()

                    // 503 Service Unavailable
                    ->scalarNode('error_503')
                        ->info('Path to the response template file used as a model for formatting service unavailable responses (e.g. 503 Service Unavailable).')
                        ->defaultNull()
                    ->end()

                    // 504 Gateway Timeout
                    ->scalarNode('error_504')
                        ->info('Path to the response template file used as a model for formatting gateway timeout responses (e.g. 504 Gateway Timeout).')
                        ->defaultNull()
                    ->end()


                    // -- Auth templates --

                    // Auth: Login
                    ->scalarNode('login')
                        ->info('Path to the response template file used as a model for formatting login responses.')
                        ->defaultNull()
                    ->end()

                    // Auth: Logout
                    ->scalarNode('logout')
                        ->info('Path to the response template file used as a model for formatting logout responses.')
                        ->defaultNull()
                    ->end()

                    // Auth: Refresh Token
                    ->scalarNode('refresh_token')
                        ->info('Path to the response template file used as a model for formatting refresh token responses.')
                        ->defaultNull()
                    ->end()

                    // Auth: Register
                    ->scalarNode('register')
                        ->info('Path to the response template file used as a model for formatting registration responses.')
                        ->defaultNull()
                    ->end()

                    // Auth: Me
                    ->scalarNode('me')
                        ->info('Path to the response template file used as a model for formatting "me" responses.')
                        ->defaultNull()
                    ->end()

                    // Auth: Account
                    ->scalarNode('account')
                        ->info('Path to the response template file used as a model for formatting account responses.')
                        ->defaultNull()
                    ->end()

                    // Auth: Update Password
                    ->scalarNode('update_password')
                        ->info('Path to the response template file used as a model for formatting update password responses.')
                        ->defaultNull()
                    ->end()

                    // Auth: Reset Password
                    ->scalarNode('reset_password')
                        ->info('Path to the response template file used as a model for formatting reset password responses.')
                        ->defaultNull()
                    ->end()
                        

                    // -- Entities templates --
                    
                    // Entity: Empty
                    ->scalarNode('empty')
                        ->info('Path to the response template file used as a model for formatting empty responses.')
                        ->defaultNull()
                    ->end()

                    // Entity: List
                    ->scalarNode('list')
                        ->info('Path to the response template file used as a model for formatting entity list responses.')
                        ->defaultNull()
                    ->end()
                    
                    // Entity: Single
                    ->scalarNode('single')
                        ->info('Path to the response template file used as a model for formatting single entity responses.')
                        ->defaultNull()
                    ->end()

                    // Entity: Created
                    ->scalarNode('created')
                        ->info('Path to the response template file used as a model for formatting entity creation responses.')
                        ->defaultNull()
                    ->end()

                    // Entity: Updated
                    ->scalarNode('updated')
                        ->info('Path to the response template file used as a model for formatting entity update responses.')
                        ->defaultNull()
                    ->end()

                    // Entity: Deleted
                    ->scalarNode('deleted')
                        ->info('Path to the response template file used as a model for formatting entity deletion responses.')
                        ->defaultNull()
                    ->end()


                    // -- System templates --

                    // System: Health
                    ->scalarNode('health')
                        ->info('Path to the response template file used as a model for formatting system health check responses.')
                        ->defaultNull()
                    ->end()

                    // System: Maintenance
                    ->scalarNode('maintenance')
                        ->info('Path to the response template file used as a model for formatting system maintenance responses.')
                        ->defaultNull()
                    ->end()

                    // System: Rate_limit
                    ->scalarNode('rate_limit')
                        ->info('Path to the response template file used as a model for formatting system rate limit responses.')
                        ->defaultNull()
                    ->end()

                    
                    // -- Files templates --

                    // Files: Upload
                    ->scalarNode('upload')
                        ->info('Path to the response template file used as a model for formatting file upload responses.')
                        ->defaultNull()
                    ->end()

                    // Files: Download
                    ->scalarNode('download')
                        ->info('Path to the response template file used as a model for formatting file download responses.')
                        ->defaultNull()
                    ->end()

                ->end()
            ->end() // of provider templates

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
                                ->values(array_merge(array_keys(MimeType::toArray(true)), [null]))
                                ->defaultNull()
                                // ->defaultValue('json')
                                // ->treatNullLike('json')
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
                                ->defaultNull()
                                // ->defaultFalse()
                                // ->treatNullLike(false)
                            ->end()

                            // URL parameter name for format override
                            ->scalarNode('parameter')
                                ->info('Name of the URL parameter used to override the response format.')
                                ->defaultValue('format')
                                ->treatNullLike('format')
                            ->end()

                        ->end()
                    ->end()

                    // Pretty print JSON responses
                    ->arrayNode('pretty_print')
                        ->info('Pretty print JSON responses for better readability.')
                        ->addDefaultsIfNotSet()->children()

                            ->booleanNode('enabled')
                                ->info('Enable pretty printing for JSON responses.')
                                ->defaultNull()
                            ->end()

                        ->end()
                    ->end()

                    // Security settings
                    ->arrayNode('security')
                        ->info('Security settings for API responses.')
                        ->addDefaultsIfNotSet()->children()

                            // Prevent JSON hijacking
                            ->arrayNode('hijacking_prevent')
                                ->info('Prevent JSON hijacking attacks.')
                                ->addDefaultsIfNotSet()->children()

                                    ->booleanNode('enabled')
                                        ->info('Enable JSON hijacking prevention.')
                                        ->defaultNull()
                                    ->end()

                                    // X-Frame-Options prefix
                                    ->enumNode('x_frame_options')
                                        ->info('Prefix added to JSON responses to prevent hijacking.')
                                        ->values(['DENY', 'SAMEORIGIN', 'ALLOW-FROM'])
                                        ->defaultNull()
                                    ->end()
                                ->end()
                            ->end()

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
                                ->defaultValue(-1)
                                ->treatNullLike(-1)
                                ->min(-1)
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
            ->end() // of provider response

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
            ->end() // of provider serialization

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
            ->end() // of provider access_control

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
                        ->end() // of authentication enabled

                        // ──────────────────────────────
                        // Collection name
                        // ──────────────────────────────
                        ->scalarNode('name')
                            ->info('Name / Alias of the entity')
                            ->defaultNull()
                            ->treatNullLike(null)
                        ->end() // of authentication name

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
                        ->end() // of authentication deprecation

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
                        ->end() // of authentication routes

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
                        ->end() // of authentication URL support

                        // ──────────────────────────────
                        // Template
                        // ──────────────────────────────
                        ->arrayNode('templates')
                            ->info('Paths to the response template files used as models for formatting the API output for lists and single items.')
                            ->addDefaultsIfNotSet()->children()

                                // Auth: Login
                                ->scalarNode('login')
                                    ->info('Path to the response template file used as a model for formatting login responses.')
                                    ->defaultNull()
                                ->end()

                                // Auth: Logout
                                ->scalarNode('logout')
                                    ->info('Path to the response template file used as a model for formatting logout responses.')
                                    ->defaultNull()
                                ->end()

                                // Auth: Refresh Token
                                ->scalarNode('refresh_token')
                                    ->info('Path to the response template file used as a model for formatting refresh token responses.')
                                    ->defaultNull()
                                ->end()

                                // Auth: Register
                                ->scalarNode('register')
                                    ->info('Path to the response template file used as a model for formatting registration responses.')
                                    ->defaultNull()
                                ->end()

                                // Auth: Me
                                ->scalarNode('me')
                                    ->info('Path to the response template file used as a model for formatting "me" responses.')
                                    ->defaultNull()
                                ->end()

                                // Auth: Account
                                ->scalarNode('account')
                                    ->info('Path to the response template file used as a model for formatting account responses.')
                                    ->defaultNull()
                                ->end()

                                // Auth: Update Password
                                ->scalarNode('update_password')
                                    ->info('Path to the response template file used as a model for formatting update password responses.')
                                    ->defaultNull()
                                ->end()

                                // Auth: Reset Password
                                ->scalarNode('reset_password')
                                    ->info('Path to the response template file used as a model for formatting reset password responses.')
                                    ->defaultNull()
                                ->end()

                            ->end()
                        ->end() // of authentication templates

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
                        ->end() // of authentication serialization

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
                                ->end() // of authentication register endpoint
                                
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
                                ->end() // of authentication login endpoint
                                
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
                                ->end() // of authentication logout endpoint
                                
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
                                ->end() // of authentication logout endpoint
                                
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
                                ->end() // of authentication password reset endpoint
                                
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
                                ->end() // of email verification endpoint
                                
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
                                ->end() // of email verification endpoint

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
                                ->end() // of password reset properties

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
                                ->end() // of authentication registration endpoint

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
                                ->end() // of authentication password change endpoint

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
                                ->end() // of authentication password change endpoint

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
                                ->end() // of authentication profile endpoint

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
                                ->end() // of authentication 2FA enable endpoint

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
                                ->end() // of authentication 2FA enable endpoint

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
                                ->end() // of authentication 2FA verify endpoint

                            ->end()
                        ->end() // of authentication 2FA verify endpoint

                    ->end()
                ->end()
            ->end() // of authentication segment

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
                        ->end() // of collection enabled

                        // ──────────────────────────────
                        // Collection name
                        // ──────────────────────────────
                        ->scalarNode('name')
                            ->info('Collection name in URLs and route names. Auto-generated from entity if null (e.g. App\\Entity\\Book → books).')
                            ->defaultNull()
                        ->end() // of collection name

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
                        ->end() // of collection deprecation

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
                        ->end() // of collection routes

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
                        ->end() // of collection pagination

                        // ──────────────────────────────
                        // URL support
                        // ──────────────────────────────
                        ->arrayNode('url_support')
                            ->info('URL Support (in response) for this collection.')
                            ->addDefaultsIfNotSet()->children()

                                // Enable or disable URL support in API responses
                                ->booleanNode('enabled')
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
                        ->end() // of collection url support

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
                        ->end() // of collection rate limit

                        // ──────────────────────────────
                        // Template
                        // ──────────────────────────────
                        ->arrayNode('templates')
                            ->info('Paths to the response template files used as models for formatting the API output for lists and single items.')
                            ->addDefaultsIfNotSet()->children()
                    
                                // Entity: Empty
                                ->scalarNode('empty')
                                    ->info('Path to the response template file used as a model for formatting empty entity responses.')
                                    ->defaultNull()
                                ->end()

                                // Entity: List
                                ->scalarNode('list')
                                    ->info('Path to the response template file used as a model for formatting entity list responses.')
                                    ->defaultNull()
                                ->end()
                                
                                // Entity: Single
                                ->scalarNode('single')
                                    ->info('Path to the response template file used as a model for formatting single entity responses.')
                                    ->defaultNull()
                                ->end()

                                // Entity: Created
                                ->scalarNode('created')
                                    ->info('Path to the response template file used as a model for formatting entity creation responses.')
                                    ->defaultNull()
                                ->end()

                                // Entity: Updated
                                ->scalarNode('updated')
                                    ->info('Path to the response template file used as a model for formatting entity update responses.')
                                    ->defaultNull()
                                ->end()

                                // Entity: Deleted
                                ->scalarNode('deleted')
                                    ->info('Path to the response template file used as a model for formatting entity deletion responses.')
                                    ->defaultNull()
                                ->end()

                            ->end()
                        ->end() // of collection templates

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
                        ->end() // of collection serialization

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
                        ->end() // of collection access control

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
                                    ->end() // of collection endpoint enabled
                                    
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
                                    ->end() // of collection endpoint deprecation

                                    // ──────────────────────────────
                                    // Route config
                                    // ──────────────────────────────
                                    ->arrayNode('route')
                                        ->info('Defines the HTTP configuration for the endpoint: route name, path, HTTP methods, controller, constraints, and routing options.')
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
                                    ->end() // of collection endpoint routes

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
                                    ->end() // of collection endpoint pagination

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
                                    ->end() // of collection endpoint rate limit

                                    // ──────────────────────────────
                                    // Template
                                    // ──────────────────────────────
                                    ->arrayNode('templates')
                                        ->info('Paths to the response template files used as models for formatting the API output for lists and single items.')
                                        ->addDefaultsIfNotSet()->children()

                    
                                            // Entity: Empty
                                            ->scalarNode('empty')
                                                ->info('Path to the response template file used as a model for formatting empty entity responses.')
                                                ->defaultNull()
                                            ->end()

                                            // Entity: List
                                            ->scalarNode('list')
                                                ->info('Path to the response template file used as a model for formatting entity list responses.')
                                                ->defaultNull()
                                            ->end()
                                            
                                            // Entity: Single
                                            ->scalarNode('single')
                                                ->info('Path to the response template file used as a model for formatting single entity responses.')
                                                ->defaultNull()
                                            ->end()

                                            // Entity: Created
                                            ->scalarNode('created')
                                                ->info('Path to the response template file used as a model for formatting entity creation responses.')
                                                ->defaultNull()
                                            ->end()

                                            // Entity: Updated
                                            ->scalarNode('updated')
                                                ->info('Path to the response template file used as a model for formatting entity update responses.')
                                                ->defaultNull()
                                            ->end()

                                            // Entity: Deleted
                                            ->scalarNode('deleted')
                                                ->info('Path to the response template file used as a model for formatting entity deletion responses.')
                                                ->defaultNull()
                                            ->end()

                                        ->end()
                                    ->end() // of collection endpoint templates

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
                                    ->end() // of collection endpoint serialization

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
                                    ->end() // of collection endpoint repository

                                    // ──────────────────────────────
                                    // Metadata config
                                    // ──────────────────────────────
                                    ->arrayNode('metadata')
                                        ->info('Free-form metadata for documentation and templating.')
                                        ->normalizeKeys(false)
                                        ->useAttributeAsKey('name')
                                        ->variablePrototype()->end()
                                    ->end() // of collection endpoint metadata

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
                                    ->end() // of collection access control

                                ->end()
                            ->end()
                        ->end() // of collection endpoints

                    ->end() // of collections arrayPrototype children
                ->end() // of collections arrayPrototype

                // Validation: entity existence
                ->validate()
                    ->IfTrue(fn($v) => EntityValidator::validateClassesExist(array_keys($v)))
                    ->thenInvalid('One or more entities defined in "api" do not exist. Check namespaces and spelling.')
                ->end()

            ->end() // of collections

            // ──────────────────────────────
            // Debug
            // ──────────────────────────────
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

        ->end()

    ->end() // of providers

    ->end() // of rootNode > children
    ->end() // of rootNode


    // ──────────────────────────────
    // Final post-processing
    // ──────────────────────────────
    ->validate()
        ->always(function($config) {

            // Enabled
            // -> Provider level
            // -> Collections level
            // -> Endpoint level
            IsEnabledResolver::execute($config);

            // Collections names (alias)
            // -> Collections level
            NameResolver::execute($config);

            // Deprecation
            // -> Provider level
            // -> Collections level
            // -> Endpoint level
            DeprecationResolver::execute($config);

            // API Resolver (API Versioning)
            // -> Provider level
            ApiResolver::execute($config);

            // TODO: create VersionResolver
            // See ApiResolver
            // VersionResolver::execute($config);

            // Route 
            // -> Provider level
            // -> Collections level
            // -> Endpoint level
            RouteResolver::execute($config);

            // Pagination
            // -> Provider level
            // -> Collections level
            // -> Endpoint level
            PaginationResolver::execute($config);

            // URL Support
            // -> Provider level
            // -> Collections level
            // UrlSupportResolver::execute($config);

            // Rate limit
            // -> Provider level
            // -> Collections level (collections)
            // -> Endpoint level
            RateLimitResolver::execute($config);

            // Templates paths
            // -> Provider level
            // -> Collections level
            // -> Endpoint level
            TemplatesResolver::execute($config);

            // Response
            ResponseResolver::execute($config);

            // Serialization
            // -> Provider level
            // -> Collections level
            // -> Endpoint level
            SerializationResolver::execute($config);

            // Access Control
            // -> Provider level
            // -> Collections level
            // -> Endpoint level
            AccessControlResolver::execute($config);

            return $config;
        })
    ->end()
    ;
};