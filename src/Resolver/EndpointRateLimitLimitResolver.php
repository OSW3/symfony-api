<?php 
namespace OSW3\Api\Resolver;

final class EndpointRateLimitLimitResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint) {

                    if (
                        !isset($endpoint['rate_limit']['limit']) || 
                        (empty($endpoint['rate_limit']['limit']) && !empty($collection['rate_limit']['limit']))
                    ) {
                        $endpoint['rate_limit']['limit'] = $collection['rate_limit']['limit'] ?? [];
                    }
                    
                }

            }
        }

        return $providers;
    }
}