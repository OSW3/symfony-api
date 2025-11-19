<?php 
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\ContextService;

final class EndpointRouteNameResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider[ContextService::SEGMENT_COLLECTION] as &$collection) {
                foreach ($collection['endpoints'] as &$endpoint)  {
                    if (empty(trim($endpoint['route']['name'])))
                    {
                        $endpoint['route']['name'] = $collection['route']['pattern'];
                    }
                }
            }
            foreach ($provider[ContextService::SEGMENT_AUTHENTICATION] as &$collection) {
                foreach ($collection['endpoints'] as $e => &$endpoint)  {
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
            foreach ($provider[ContextService::SEGMENT_COLLECTION] as $collectionName => &$collection) {
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
            foreach ($provider[ContextService::SEGMENT_AUTHENTICATION] as $collectionName => &$collection) {
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