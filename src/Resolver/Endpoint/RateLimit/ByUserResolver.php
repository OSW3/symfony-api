<?php 
namespace OSW3\Api\Resolver\Endpoint\RateLimit;

final class ByUserResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint) {

                    if (
                        !isset($endpoint['rate_limit']['by_user']) || 
                        (empty($endpoint['rate_limit']['by_user']) && !empty($collection['rate_limit']['by_user']))
                    ) {
                        $endpoint['rate_limit']['by_user'] = $collection['rate_limit']['by_user'] ?? [];
                    }
                    
                }

            }
        }

        return $providers;
    }
}