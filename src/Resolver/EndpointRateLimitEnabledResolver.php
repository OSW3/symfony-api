<?php 
namespace OSW3\Api\Resolver;

final class EndpointRateLimitEnabledResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint) {

                    if (
                        !isset($endpoint['rate_limit']['enabled']) || 
                        (empty($endpoint['rate_limit']['enabled']) && !empty($collection['rate_limit']['enabled']))
                    ) {
                        $endpoint['rate_limit']['enabled'] = $collection['rate_limit']['enabled'] ?? [];
                    }
                    
                }

            }
        }

        return $providers;
    }
}