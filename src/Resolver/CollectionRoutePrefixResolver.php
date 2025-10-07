<?php 
namespace OSW3\Api\Resolver;

final class CollectionRoutePrefixResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {
                if (empty(trim($collection['route']['prefix']))) 
                {
                    $collection['route']['prefix'] = $provider['routes']['prefix'];
                }
            }
        }

        return $providers;
    }

    public static function resolve(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {

                $versionNumber = $provider['version']['number'];
                $versionPrefix = $provider['version']['prefix'];
                $fullVersion = "{$versionPrefix}{$versionNumber}";

                $collection['route']['prefix'] = preg_replace("/{version}/", $fullVersion, $collection['route']['prefix']);
            }
        }

        return $providers;
    }
}