<?php 
namespace OSW3\Api\Resolver\Collection\RateLimit;

final class IsEnabledResolver
{
    public static function default(array &$providers): array
    {
        foreach ($providers as &$provider) {
            $providerRateLimit = $provider['rate_limit']['enabled'] ?? true;

            foreach ($provider['collections'] as &$collection) {
                $value = $collection['rate_limit']['enabled'] ?? null;

                if ($value === null) {
                    $collection['rate_limit']['enabled'] = $providerRateLimit;
                }
            }
        }

        return $providers;
    }
}