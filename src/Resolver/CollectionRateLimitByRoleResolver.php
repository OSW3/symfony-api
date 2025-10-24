<?php 
namespace OSW3\Api\Resolver;

final class CollectionRateLimitByRoleResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {

                if (
                    !isset($collection['rate_limit']['by_role']) || 
                    (empty($collection['rate_limit']['by_role']) && !empty($provider['rate_limit']['by_role']))
                ) {
                    $collection['rate_limit']['by_role'] = $provider['rate_limit']['by_role'] ?? [];
                }

            }
        }

        return $providers;
    }

}