<?php 
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\UtilsService;

final class CollectionDeprecationRemovalDateResolver
{
    public static function default(array &$providers): array
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {

                if (empty($collection['deprecation']['removal_date'])) {
                    $collection['deprecation']['removal_date'] = $provider['deprecation']['removal_date'] ?? null;
                }

            }
        }

        return $providers;
    }

    public static function resolve(array &$providers): array
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {

                if (UtilsService::is_date($collection['deprecation']['removal_date'])) {
                    $collection['deprecation']['removal_date'] = UtilsService::to_http_date($collection['deprecation']['removal_date']);
                }

            }
        }

        return $providers;
    }
}