<?php 
namespace OSW3\Api\Resolver;

final class EndpointRoutePathResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {
                foreach ($collection['endpoints'] as &$endpoint)  {
                    if (empty(trim($endpoint['route']['path'])))
                    {
                        $endpoint['route']['path'] = "{$collection['route']['prefix']}/{$collection['name']}";
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
                    $endpoint['route']['path'] = preg_replace("/{version}/", $fullVersion, $endpoint['route']['path']);
                    $endpoint['route']['path'] = preg_replace("/{action}/", $endpointName, $endpoint['route']['path']);
                    $endpoint['route']['path'] = preg_replace("/{collection}/", $collection['name'], $endpoint['route']['path']);
                }
            }
        }

        return $providers;
    }
}