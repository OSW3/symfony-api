<?php 
namespace OSW3\Api\Resolver;

final class CollectionRateLimitEnabledResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {
                
                if (!isset($collection['rate_limit']['enabled'])) {
                    $collection['rate_limit']['enabled'] = $provider['rate_limit']['enabled'] ?? false;
                }

            }
        }

        return $providers;
    }

}