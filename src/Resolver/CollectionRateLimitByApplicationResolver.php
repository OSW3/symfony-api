<?php 
namespace OSW3\Api\Resolver;

final class CollectionRateLimitByApplicationResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {

                if (
                    !isset($collection['rate_limit']['by_application']) ||
                    (empty($collection['rate_limit']['by_application']) && !empty($provider['rate_limit']['by_application']))
                ) {
                    $collection['rate_limit']['by_application'] = $provider['rate_limit']['by_application'] ?? [];
                }

            }
        }

        return $providers;
    }

}