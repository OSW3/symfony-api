<?php 
namespace OSW3\Api\Resolver\Endpoint\Deprecation;

use OSW3\Api\Service\UtilsService;

final class StartAtResolver
{
    public static function default(array &$providers): array
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint) {

                    if (empty($endpoint['deprecation']['start_at'])) {
                        $endpoint['deprecation']['start_at'] = $collection['deprecation']['start_at'] ?? null;
                    }

                }
            }
        }

        return $providers;
    }

    public static function resolve(array &$providers): array
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint) {

                    if (UtilsService::is_date($endpoint['deprecation']['start_at'])) {
                        $endpoint['deprecation']['start_at'] = UtilsService::to_http_date($endpoint['deprecation']['start_at']);
                    }

                }
            }
        }

        return $providers;
    }
}