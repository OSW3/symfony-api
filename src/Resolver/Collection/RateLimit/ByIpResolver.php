<?php 
namespace OSW3\Api\Resolver\Collection\RateLimit;

final class ByIpResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {

                if (
                    !isset($collection['rate_limit']['by_ip']) ||
                    (empty($collection['rate_limit']['by_ip']) && !empty($provider['rate_limit']['by_ip']))
                ) {
                    $collection['rate_limit']['by_ip'] = $provider['rate_limit']['by_ip'] ?? [];
                }

            }
        }

        return $providers;
    }
}