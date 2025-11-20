<?php 
namespace OSW3\Api\Resolver\Endpoint\RateLimit;

final class IncludeHeadersResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint) {

                    if (
                        !isset($endpoint['rate_limit']['include_headers']) || 
                        (empty($endpoint['rate_limit']['include_headers']) && !empty($collection['rate_limit']['include_headers']))
                    ) {
                        $endpoint['rate_limit']['include_headers'] = $collection['rate_limit']['include_headers'] ?? [];
                    }
                    
                }

            }
        }

        return $providers;
    }
}