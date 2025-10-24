<?php 
namespace OSW3\Api\Resolver;

final class CollectionSearchStatusResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {

                // dd($collection['search']['enabled'], $provider['search']['enabled']);
                if (
                    !isset($collection['search']['enabled']) || 
                    !is_bool($collection['search']['enabled']) ||
                    ($provider['search']['enabled'] === false && $collection['search']['enabled'] !== $provider['search']['enabled'])
                ){
                    $collection['search']['enabled'] = $provider['search']['enabled'];
                }
            }
        }

        return $providers;
    }

}