<?php 
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\UtilsService;

final class EndpointDeprecationSinceDateResolver
{
    public static function default(array &$providers): array
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint) {

                    if (empty($endpoint['deprecation']['since_date'])) {
                        $endpoint['deprecation']['since_date'] = $collection['deprecation']['since_date'] ?? null;
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

                    if (UtilsService::is_date($endpoint['deprecation']['since_date'])) {
                        $endpoint['deprecation']['since_date'] = UtilsService::to_http_date($endpoint['deprecation']['since_date']);
                    }

                }
            }
        }

        return $providers;
    }
}