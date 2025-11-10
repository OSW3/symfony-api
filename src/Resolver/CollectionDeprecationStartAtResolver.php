<?php 
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\UtilsService;

final class CollectionDeprecationStartAtResolver
{
    public static function default(array &$providers): array
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {

                if (empty($collection['deprecation']['start_at'])) {
                    $collection['deprecation']['start_at'] = $provider['deprecation']['start_at'] ?? null;
                }

            }
        }

        return $providers;
    }

    public static function resolve(array &$providers): array
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {

                if (UtilsService::is_date($collection['deprecation']['start_at'])) {
                    $collection['deprecation']['start_at'] = UtilsService::to_http_date($collection['deprecation']['start_at']);
                }

            }
        }

        return $providers;
    }
}