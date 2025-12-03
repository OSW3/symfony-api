<?php 
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\ContextService;

final class IsEnabledResolver
{
    // Segments to treat
    const SEGMENTS = [
        ContextService::SEGMENT_AUTHENTICATION,
        ContextService::SEGMENT_COLLECTION,
    ];

    public static function execute(array &$config): array
    {
        foreach ($config['providers'] as &$provider) {

            // Secure the key 'enabled' of the provider
            $providerEnabled = (bool)($provider['enabled'] ?? false);

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

                    // Handle 'enabled' key for collection
                    if (!$providerEnabled) {
                        $collection['enabled'] = false;
                    } elseif (!array_key_exists('enabled', $collection) || $collection['enabled'] === null) {
                        $collection['enabled'] = $providerEnabled;
                    }

                    $collectionEnabled = (bool)$collection['enabled'];

                    // Check key 'endpoints'
                    if (empty($collection['endpoints']) || !is_array($collection['endpoints'])) {
                        continue;
                    }

                    foreach ($collection['endpoints'] as $endpointName => $endpoint) {

                        if (!is_array($endpoint)) {
                            continue;
                        }

                        if (!$collectionEnabled) {
                            $endpoint['enabled'] = false;
                        } elseif (!array_key_exists('enabled', $endpoint) || $endpoint['enabled'] === null) {
                            $endpoint['enabled'] = $collectionEnabled;
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