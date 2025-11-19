<?php 
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\ContextService;

final class CollectionRoutePatternResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider[ContextService::SEGMENT_COLLECTION] as &$collection) {
                if (empty(trim($collection['route']['pattern']))) 
                {
                    $collection['route']['pattern'] = $provider['routes']['pattern'];
                }
            }
            foreach ($provider[ContextService::SEGMENT_AUTHENTICATION] as &$collection) {
                if (empty(trim($collection['route']['pattern']))) 
                {
                    $collection['route']['pattern'] = $provider['routes']['pattern'];
                }
            }
        }

        return $providers;
    }
}