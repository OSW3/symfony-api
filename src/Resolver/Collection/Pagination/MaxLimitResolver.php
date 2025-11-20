<?php 
namespace OSW3\Api\Resolver\Collection\Pagination;

final class MaxLimitResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {

                if (
                    !isset($collection['pagination']['max_limit']) || 
                    $collection['pagination']['max_limit'] === null || 
                    $collection['pagination']['max_limit'] <= -1
                ){
                    $collection['pagination']['max_limit'] = $provider['pagination']['max_limit'];
                }

            }
        }

        return $providers;
    }
}