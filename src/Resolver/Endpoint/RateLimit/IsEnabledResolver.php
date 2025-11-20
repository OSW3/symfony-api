<?php 
namespace OSW3\Api\Resolver\Endpoint\RateLimit;

final class IsEnabledResolver
{
    public static function default(array &$providers): array
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {
                $collectionRateLimit = $collection['rate_limit']['enabled'] ?? ($provider['rate_limit']['enabled'] ?? true);

                foreach ($collection['endpoints'] as &$endpoint) {
                    $value = $endpoint['rate_limit']['enabled'] ?? null;

                    if ($value === null) {
                        $endpoint['rate_limit']['enabled'] = $collectionRateLimit;
                    }
                }
            }
        }

        return $providers;
    }
}