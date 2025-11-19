<?php 
namespace OSW3\Api\Resolver;

final class ProviderAuthenticationRoutePrefixResolver
{
    public static function resolve(array &$providers): array
    {
        foreach ($providers as &$config) {
            
            if (count($config['authentication']) <= 1) {
                continue;
            }

            $prefixes = [];
            foreach ($config['authentication'] as &$options) {

                $prefix = $options['route']['prefix'];
                $name = $options['name'];

                if (in_array($prefix, $prefixes)) {
                    $prefix = "/{$name}{$prefix}";
                    $options['route']['prefix'] = $prefix;
                }

                array_push($prefixes, $prefix);
            }
        }

        return $providers;
    }

}