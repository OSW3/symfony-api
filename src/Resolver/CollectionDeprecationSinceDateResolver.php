<?php 
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\UtilsService;

final class CollectionDeprecationSinceDateResolver
{
    public static function default(array &$providers): array
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {

                if (empty($collection['deprecation']['since_date'])) {
                    $collection['deprecation']['since_date'] = $provider['deprecation']['since_date'] ?? null;
                }

            }
        }

        return $providers;
    }

    public static function resolve(array &$providers): array
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {

                if (UtilsService::is_date($collection['deprecation']['since_date'])) {
                    $collection['deprecation']['since_date'] = UtilsService::to_http_date($collection['deprecation']['since_date']);
                }

            }
        }

        return $providers;
    }
}