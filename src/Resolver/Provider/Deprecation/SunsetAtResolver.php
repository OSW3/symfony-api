<?php 
namespace OSW3\Api\Resolver\Provider\Deprecation;

use OSW3\Api\Service\UtilsService;

final class SunsetAtResolver
{
    public static function resolve(array &$providers): array
    {
        foreach ($providers as &$config) {

            if (UtilsService::is_date($config['deprecation']['sunset_at'])) {
                $config['deprecation']['sunset_at'] = UtilsService::to_http_date($config['deprecation']['sunset_at']);
            }

        }

        return $providers;
    }

}