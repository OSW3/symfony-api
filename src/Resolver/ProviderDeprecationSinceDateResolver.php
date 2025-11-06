<?php 
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\UtilsService;

final class ProviderDeprecationSinceDateResolver
{
    public static function resolve(array &$providers): array
    {
        foreach ($providers as &$config) {

            if (UtilsService::is_date($config['deprecation']['since_date'])) {
                $config['deprecation']['since_date'] = UtilsService::to_http_date($config['deprecation']['since_date']);
            }

        }

        return $providers;
    }

}