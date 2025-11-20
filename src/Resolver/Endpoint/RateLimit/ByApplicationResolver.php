<?php 
namespace OSW3\Api\Resolver\Endpoint\RateLimit;

final class ByApplicationResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint) {

                    if (
                        !isset($endpoint['rate_limit']['by_application']) || 
                        (empty($endpoint['rate_limit']['by_application']) && !empty($collection['rate_limit']['by_application']))
                    ) {
                        $endpoint['rate_limit']['by_application'] = $collection['rate_limit']['by_application'] ?? [];
                    }
                    
                }

            }
        }

        return $providers;
    }
}