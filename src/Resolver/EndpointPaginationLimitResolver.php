<?php 
namespace OSW3\Api\Resolver;

final class EndpointPaginationLimitResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint)  {

                    if (!isset($endpoint['pagination']['limit']) || $endpoint['pagination']['limit'] === null || $endpoint['pagination']['limit'] <= -1) 
                    {
                        $endpoint['pagination']['limit'] = $collection['pagination']['limit'];
                    }

                }
            }
        }

        return $providers;
    }
}