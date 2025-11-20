<?php 
namespace OSW3\Api\Resolver\Endpoint\Deprecation;

use OSW3\Api\Service\UtilsService;

final class SunsetAtResolver
{
    public static function default(array &$providers): array
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint) {

                    if (empty($endpoint['deprecation']['sunset_at'])) {
                        $endpoint['deprecation']['sunset_at'] = $collection['deprecation']['sunset_at'] ?? null;
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

                    if (UtilsService::is_date($endpoint['deprecation']['sunset_at'])) {
                        $endpoint['deprecation']['sunset_at'] = UtilsService::to_http_date($endpoint['deprecation']['sunset_at']);
                    }

                }
            }
        }

        return $providers;
    }
}