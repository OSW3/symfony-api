<?php 
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\ContextService;

final class CollectionRoutePrefixResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider[ContextService::SEGMENT_COLLECTION] as &$collection) {
                if (empty(trim($collection['route']['prefix']))) 
                {
                    $collection['route']['prefix'] = $provider['routes']['prefix']; // "/api"
                }
            }
            foreach ($provider[ContextService::SEGMENT_AUTHENTICATION] as &$collection) {
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
            foreach ($provider[ContextService::SEGMENT_COLLECTION] as &$collection) {
                $path = "";
                $path.= "/". trim($collection['route']['prefix'], "/");

                $collection['route']['prefix'] = $path; // "/api/v1"
            }
            foreach ($provider[ContextService::SEGMENT_AUTHENTICATION] as &$collection) {
                $path = "";
                $path.= "/". trim($collection['route']['prefix'], "/");
                $path.= "/". trim($collection['route']['additional_prefix'], "/");

                $collection['route']['prefix'] = $path; // "/api/v1"
            }
        }

        return $providers;
    }
}