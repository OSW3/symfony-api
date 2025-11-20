<?php 
namespace OSW3\Api\Resolver\Collection\RateLimit;

final class ByUserResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {

                if (
                    !isset($collection['rate_limit']['by_user'])
                    || (empty($collection['rate_limit']['by_user']) && !empty($provider['rate_limit']['by_user']))
                ) {
                    $collection['rate_limit']['by_user'] = $provider['rate_limit']['by_user'] ?? [];
                }

            }
        }

        return $providers;
    }
}