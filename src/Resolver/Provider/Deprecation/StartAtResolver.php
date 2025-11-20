<?php 
namespace OSW3\Api\Resolver\Provider\Deprecation;

use OSW3\Api\Service\UtilsService;

final class StartAtResolver
{
    public static function resolve(array &$providers): array
    {
        foreach ($providers as &$config) {

            if (UtilsService::is_date($config['deprecation']['start_at'])) {
                $config['deprecation']['start_at'] = UtilsService::to_http_date($config['deprecation']['start_at']);
            }

        }

        return $providers;
    }

}