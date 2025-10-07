<?php 
namespace OSW3\Api\Resolver;

final class CollectionRouteNameResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {
                if (empty(trim($collection['route']['name']))) 
                {
                    $collection['route']['name'] = $provider['routes']['name'];
                }
            }
        }

        return $providers;
    }
}