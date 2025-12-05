<?php 
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\UtilsService;
use OSW3\Api\Service\ContextService;

final class DeprecationResolver
{
    const SEGMENTS = [
        ContextService::SEGMENT_AUTHENTICATION,
        ContextService::SEGMENT_COLLECTION,
    ];
    
    public static function execute(array &$config): array 
    {
        foreach ($config['providers'] as &$provider) {

            // Secure the keys of the provider
            $providerEnabled   = (bool)($provider['deprecation']['enabled'] ?? false);
            $providerStartAt   = (int)($provider['deprecation']['start_at'] ?? null);
            $providerSunsetAt  = (int)($provider['deprecation']['sunset_at'] ?? null);
            $providerLink      = (string)($provider['deprecation']['link'] ?? null);
            $providerSuccessor = (string)($provider['deprecation']['successor'] ?? null);
            $providerMessage   = (string)($provider['deprecation']['message'] ?? null);

            if (!$providerEnabled) {
                $provider['deprecation']['start_at'] = null;
                $provider['deprecation']['sunset_at'] = null;
            }
            if (UtilsService::is_date($provider['deprecation']['start_at'])) {
                $provider['deprecation']['start_at'] = UtilsService::to_http_date($provider['deprecation']['start_at']);
            }
            if (UtilsService::is_date($provider['deprecation']['sunset_at'])) {
                $provider['deprecation']['sunset_at'] = UtilsService::to_http_date($provider['deprecation']['sunset_at']);
            }

            foreach (static::SEGMENTS as $segment) {

                // Security: missing segment
                if (empty($provider[$segment]) || !is_array($provider[$segment])) {
                    continue;
                }

                foreach ($provider[$segment] as &$collection) {

                    // Check collection is array
                    if (!is_array($collection)) {
                        continue;
                    }

                    // Handle deprecation 'enabled' key for collection
                    if ($providerEnabled) {
                        $collection['deprecation']['enabled'] = true;
                    } elseif (!array_key_exists('enabled', $collection['deprecation']) || $collection['deprecation']['enabled'] === null) {
                        $collection['deprecation']['enabled'] = $providerEnabled;
                    }

                    // Handle deprecation 'start_at' and 'sunset_at' keys for collection
                    if ($providerEnabled) {
                        if (empty($collection['deprecation']['start_at'])) {
                            $collection['deprecation']['start_at'] = $providerStartAt;
                        }
                        if (UtilsService::is_date($collection['deprecation']['start_at'])) {
                            $collection['deprecation']['start_at'] = UtilsService::to_http_date($collection['deprecation']['start_at']);
                        }
    
                        if (empty($collection['deprecation']['sunset_at'])) {
                            $collection['deprecation']['sunset_at'] = $providerSunsetAt;
                        }
                        if (UtilsService::is_date($collection['deprecation']['sunset_at'])) {
                            $collection['deprecation']['sunset_at'] = UtilsService::to_http_date($collection['deprecation']['sunset_at']);
                        }
    
                        if (empty($collection['deprecation']['link'])) {
                            $collection['deprecation']['link'] = $providerLink;
                        }

                        if (empty($collection['deprecation']['successor'])) {
                            $collection['deprecation']['successor'] = $providerSuccessor;
                        }

                        if (empty($collection['deprecation']['message'])) {
                            $collection['deprecation']['message'] = $providerMessage;
                        }
                    }


                    // Skip authentication segment
                    if ($segment === ContextService::SEGMENT_AUTHENTICATION) {
                        continue;
                    }

                    // Check key 'endpoints'
                    if (empty($collection['endpoints']) || !is_array($collection['endpoints'])) {
                        continue;
                    }


                    $collectionEnabled   = $collection['deprecation']['enabled'];
                    $collectionStartAt   = $collection['deprecation']['start_at'];
                    $collectionSunsetAt  = $collection['deprecation']['sunset_at'];
                    $collectionLink      = $collection['deprecation']['link'];
                    $collectionSuccessor = $collection['deprecation']['successor'];
                    $collectionMessage   = $collection['deprecation']['message'];

                    foreach ($collection['endpoints'] as $endpointName => $endpoint) {

                        if (!is_array($endpoint)) {
                            continue;
                        }

                        // Handle deprecation 'enabled' key for endpoint
                        if ($collectionEnabled) {
                            $endpoint['deprecation']['enabled'] = true;
                        } elseif (!array_key_exists('enabled', $endpoint['deprecation']) || $endpoint['deprecation']['enabled'] === null) {
                            $endpoint['deprecation']['enabled'] = $collectionEnabled;
                        }

                        // Handle deprecation 'start_at' and 'sunset_at' keys for endpoint
                        if ($collectionEnabled) {
                            if (empty($endpoint['deprecation']['start_at'])) {
                                $endpoint['deprecation']['start_at'] = $collectionStartAt;
                            }
                            if (UtilsService::is_date($endpoint['deprecation']['start_at'])) {
                                $endpoint['deprecation']['start_at'] = UtilsService::to_http_date($endpoint['deprecation']['start_at']);
                            }
    
                            if (empty($endpoint['deprecation']['sunset_at'])) {
                                $endpoint['deprecation']['sunset_at'] = $collectionSunsetAt;
                            }
                            if (UtilsService::is_date($endpoint['deprecation']['sunset_at'])) {
                                $endpoint['deprecation']['sunset_at'] = UtilsService::to_http_date($endpoint['deprecation']['sunset_at']);
                            }
        
                            if (empty($endpoint['deprecation']['link'])) {
                                $endpoint['deprecation']['link'] = $collectionLink;
                            }

                            if (empty($endpoint['deprecation']['successor'])) {
                                $endpoint['deprecation']['successor'] = $collectionSuccessor;
                            }

                            if (empty($endpoint['deprecation']['message'])) {
                                $endpoint['deprecation']['message'] = $collectionMessage;
                            }
                        }

                        // Re-assign endpoint
                        $collection['endpoints'][$endpointName] = $endpoint;
                    }
                }
                unset($collection); // security for loops with references
            }
        }

        return $config;
    }
}