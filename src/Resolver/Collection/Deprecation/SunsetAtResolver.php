<?php 
namespace OSW3\Api\Resolver\Collection\Deprecation;

use OSW3\Api\Service\UtilsService;

final class SunsetAtResolver
{
    public static function default(array &$providers): array
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {

                if (empty($collection['deprecation']['sunset_at'])) {
                    $collection['deprecation']['sunset_at'] = $provider['deprecation']['sunset_at'] ?? null;
                }

            }
        }

        return $providers;
    }

    public static function resolve(array &$providers): array
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {

                if (UtilsService::is_date($collection['deprecation']['sunset_at'])) {
                    $collection['deprecation']['sunset_at'] = UtilsService::to_http_date($collection['deprecation']['sunset_at']);
                }

            }
        }

        return $providers;
    }
}