<?php 
namespace OSW3\Api\Resolver\Endpoint\Deprecation;

final class IsEnabledResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {
                foreach ($collection['endpoints'] as &$endpoint)  {

                    if (!isset($endpoint['deprecation']['enabled'])) {
                        $endpoint['deprecation']['enabled'] = $collection['deprecation']['enabled'] ?? false;
                    }

                }
            }
        }

        return $providers;
    }
}