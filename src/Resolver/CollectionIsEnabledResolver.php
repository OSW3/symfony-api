<?php 
namespace OSW3\Api\Resolver;

final class CollectionIsEnabledResolver
{
    public static function default(array &$providers): array
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {

                if (!$provider['enabled']) {
                    $collection['enabled'] = false;
                    continue;
                }

                if (!isset($collection['enabled']) || $collection['enabled'] === null) {
                    $collection['enabled'] = $provider['enabled'];
                }
            }
        }

        return $providers;
    }
}