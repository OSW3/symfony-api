<?php 
namespace OSW3\Api\Resolver\Provider;

final class IsEnabledResolver
{
    public static function default(array &$providers): array
    {
        foreach ($providers as &$config) {

            // dd($config['enabled']);
            
            if (!isset($config['enabled']) || $config['enabled'] === null) {
                $config['enabled'] = true;
            }
        }

        return $providers;
    }
}