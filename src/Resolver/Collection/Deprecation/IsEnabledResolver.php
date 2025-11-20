<?php 
namespace OSW3\Api\Resolver\Collection\Deprecation;

final class IsEnabledResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {

                if (!isset($collection['deprecation']['enabled'])) {
                    $collection['deprecation']['enabled'] = $provider['deprecation']['enabled'] ?? false;
                }

            }
        }

        return $providers;
    }
}