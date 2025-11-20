<?php 
namespace OSW3\Api\Resolver\Endpoint\RateLimit;

use OSW3\Api\Service\ContextService;

final class LimitResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider[ContextService::SEGMENT_COLLECTION] as $collectionName => &$collection) {
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