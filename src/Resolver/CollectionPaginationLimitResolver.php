<?php 
namespace OSW3\Api\Resolver;

final class CollectionPaginationLimitResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {

                if (
                    !isset($collection['pagination']['limit']) || 
                    $collection['pagination']['limit'] === null || 
                    $collection['pagination']['limit'] <= -1
                ){
                    $collection['pagination']['limit'] = $provider['pagination']['limit'];
                }
                
            }
        }

        return $providers;
    }
}