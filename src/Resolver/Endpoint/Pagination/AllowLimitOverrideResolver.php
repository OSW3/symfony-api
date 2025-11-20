<?php 
namespace OSW3\Api\Resolver\Endpoint\Pagination;

final class AllowLimitOverrideResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint) {

                    if (!isset($endpoint['pagination']['allow_limit_override']) || $endpoint['pagination']['allow_limit_override'] === null) 
                    {
                        $endpoint['pagination']['allow_limit_override'] = $collection['pagination']['allow_limit_override'];
                    }

                }
            }
        }

        return $providers;
    }
}