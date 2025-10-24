<?php 
namespace OSW3\Api\Resolver;

final class CollectionPaginationEnabledResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {

                if (!isset($collection['pagination']['enabled']) || $collection['pagination']['enabled'] === null) 
                {
                    $collection['pagination']['enabled'] = $provider['pagination']['enabled'];
                }
                
            }
        }

        return $providers;
    }
}