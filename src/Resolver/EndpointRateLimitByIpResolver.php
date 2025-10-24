<?php 
namespace OSW3\Api\Resolver;

final class EndpointRateLimitByIpResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint) {

                    if (
                        !isset($endpoint['rate_limit']['by_ip']) || 
                        (empty($endpoint['rate_limit']['by_ip']) && !empty($collection['rate_limit']['by_ip']))
                    ) {
                        $endpoint['rate_limit']['by_ip'] = $collection['rate_limit']['by_ip'] ?? [];
                    }
                    
                }

            }
        }

        return $providers;
    }
}