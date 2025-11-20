<?php 
namespace OSW3\Api\Resolver\Endpoint\RateLimit;

final class ByRoleResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint) {

                    if (
                        !isset($endpoint['rate_limit']['by_role']) || 
                        (empty($endpoint['rate_limit']['by_role']) && !empty($collection['rate_limit']['by_role']))
                    ) {
                        $endpoint['rate_limit']['by_role'] = $collection['rate_limit']['by_role'] ?? [];
                    }
                    
                }

            }
        }

        return $providers;
    }
}