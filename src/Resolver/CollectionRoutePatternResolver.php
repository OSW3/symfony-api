<?php 
namespace OSW3\Api\Resolver;

final class CollectionRoutePatternResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {
                if (empty(trim($collection['route']['pattern']))) 
                {
                    $collection['route']['pattern'] = $provider['routes']['pattern'];
                }
            }
        }

        return $providers;
    }
}