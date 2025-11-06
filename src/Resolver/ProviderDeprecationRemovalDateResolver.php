<?php 
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\UtilsService;

final class ProviderDeprecationRemovalDateResolver
{
    public static function resolve(array &$providers): array
    {
        foreach ($providers as &$config) {

            if (UtilsService::is_date($config['deprecation']['removal_date'])) {
                $config['deprecation']['removal_date'] = UtilsService::to_http_date($config['deprecation']['removal_date']);
            }

        }

        return $providers;
    }

}