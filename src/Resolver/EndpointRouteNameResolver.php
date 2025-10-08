<?php 
namespace OSW3\Api\Resolver;

final class EndpointRouteNameResolver
{
    // public static function default(array $collection, array &$endpoint): array 
    // {
    //     if (empty(trim($endpoint['route']['name'])))
    //     {
    //         $endpoint['route']['name'] = $collection['route']['pattern'];
    //     }

    //     return $endpoint;
    // }


    // public static function resolve(array $provider, array &$endpoint, string $endpointName, string $collectionName): array 
    // {
    //     $versionNumber = $provider['version']['number'];
    //     $versionPrefix = $provider['version']['prefix'];
    //     $fullVersion = "{$versionPrefix}{$versionNumber}";

    //     // Generate Endpoint Route Name
    //     $className = (new \ReflectionClass($collectionName))->getShortName();
    //     $className = strtolower($className);
    //     $endpoint['route']['name'] = preg_replace("/{version}/", $fullVersion, $endpoint['route']['name']);
    //     $endpoint['route']['name'] = preg_replace("/{action}/", $endpointName, $endpoint['route']['name']);
    //     $endpoint['route']['name'] = preg_replace("/{collection}/", $className, $endpoint['route']['name']);

    //     return $endpoint;
    // }


    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {
                foreach ($collection['endpoints'] as &$endpoint)  {

                    if (empty(trim($endpoint['route']['name'])))
                    {
                        $endpoint['route']['name'] = $collection['route']['pattern'];
                    }

                }
            }
        }

        return $providers;
    }


    public static function resolve(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint)  {
                    $versionNumber = $provider['version']['number'];
                    $versionPrefix = $provider['version']['prefix'];
                    $fullVersion = "{$versionPrefix}{$versionNumber}";

                    // Generate Endpoint Route Name
                    $className = (new \ReflectionClass($collectionName))->getShortName();
                    $className = strtolower($className);
                    $endpoint['route']['name'] = preg_replace("/{version}/", $fullVersion, $endpoint['route']['name']);
                    $endpoint['route']['name'] = preg_replace("/{action}/", $endpointName, $endpoint['route']['name']);
                    $endpoint['route']['name'] = preg_replace("/{collection}/", $className, $endpoint['route']['name']);
                }
            }
        }

        return $providers;
    }
}