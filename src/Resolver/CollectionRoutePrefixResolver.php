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
                $versionType   = $provider['version']['type'];
                $path = preg_replace("#/$#", "", $collection['route']['prefix']);
                $fullVersion   = "{$versionPrefix}{$versionNumber}";

                if ($versionType === 'path') {
                    $collection['route']['prefix'] = "{$path}/{$fullVersion}";
                }
            }
        }

        return $providers;
    }
}