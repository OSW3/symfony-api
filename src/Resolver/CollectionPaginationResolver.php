<?php 
namespace OSW3\Api\Resolver;

final class CollectionPaginationResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {
                if (!isset($collection['pagination']) || $collection['pagination'] === null) 
                {
                    $collection['pagination'] = $provider['pagination']['per_page'];
                }
            }
        }

        return $providers;
    }
}