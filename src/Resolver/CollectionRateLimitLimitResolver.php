<?php 
namespace OSW3\Api\Resolver;

final class CollectionRateLimitLimitResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {

                if (!isset($collection['rate_limit']['limit'])) {
                    $collection['rate_limit']['limit'] = $provider['rate_limit']['limit'] ?? '100/hour';
                }

            }
        }

        return $providers;
    }

}