<?php 
namespace OSW3\Api\Resolver;

final class EndpointPaginationEnabledResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint)  {

                    if (!isset($endpoint['pagination']['enabled']) || $endpoint['pagination']['enabled'] === null) 
                    {
                        $endpoint['pagination']['enabled'] = $collection['pagination']['enabled'];
                    }

                }
            }
        }

        return $providers;
    }
}