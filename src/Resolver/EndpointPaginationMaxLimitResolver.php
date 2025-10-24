<?php 
namespace OSW3\Api\Resolver;

final class EndpointPaginationMaxLimitResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint)  {

                    if (!isset($endpoint['pagination']['max_limit']) || $endpoint['pagination']['max_limit'] === null || $endpoint['pagination']['max_limit'] <= -1) 
                    {
                        $endpoint['pagination']['max_limit'] = $collection['pagination']['max_limit'];
                    }

                }
            }
        }

        return $providers;
    }
}