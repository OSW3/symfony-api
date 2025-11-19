<?php 
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\ContextService;

final class EndpointRoutePathResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider[ContextService::SEGMENT_COLLECTION] as &$collection) {
                foreach ($collection['endpoints'] as &$endpoint)  {
                    if (empty(trim($endpoint['route']['path'])))
                    {
                        $endpoint['route']['path'] = "{$collection['route']['prefix']}/{$collection['name']}"; // "/api/v1/books
                    }
                }
            }

            $countAuthCollections = count($provider[ContextService::SEGMENT_AUTHENTICATION]);
            foreach ($provider[ContextService::SEGMENT_AUTHENTICATION] as &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint)  {

                    $path = "";
                    $path.= "/". trim($collection['route']['prefix'], "/");

                    // Add collection name in path if multiple auth collections
                    $path.= $countAuthCollections > 1 ? "/". trim($collection['name'], "/") : "";

                    if (empty(trim($endpoint['route']['path'])))
                    {
                        $path.= "/". trim($endpointName, "/");
                    } else {
                        $path.= "/". trim($endpoint['route']['path'], "/");
                    }
                    $endpoint['route']['path'] = $path; // "/api/v1/user/register"
                }
            }
        }
        return $providers;
    }

    public static function resolve(array &$providers): array 
    {
        foreach ($providers as &$provider) {

            $versionNumber = $provider['version']['number'];
            $versionPrefix = $provider['version']['prefix'];
            $fullVersion = "{$versionPrefix}{$versionNumber}";

            foreach ($provider[ContextService::SEGMENT_COLLECTION] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint)  {
                    $endpoint['route']['path'] = preg_replace("/{version}/", $fullVersion, $endpoint['route']['path']);
                    $endpoint['route']['path'] = preg_replace("/{action}/", $endpointName, $endpoint['route']['path']);
                    $endpoint['route']['path'] = preg_replace("/{collection}/", $collection['name'], $endpoint['route']['path']);
                }
            }

            foreach ($provider[ContextService::SEGMENT_AUTHENTICATION] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint)  {
                    $endpoint['route']['path'] = preg_replace("/{version}/", $fullVersion, $endpoint['route']['path']);
                    $endpoint['route']['path'] = preg_replace("/{action}/", $endpointName, $endpoint['route']['path']);
                    $endpoint['route']['path'] = preg_replace("/{collection}/", $collection['name'], $endpoint['route']['path']);
                }
            }
        }

        return $providers;
    }
}