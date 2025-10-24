<?php 
namespace OSW3\Api\Resolver;

final class CollectionTemplatesResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {

                if (!isset($collection['templates']['list']) || $collection['templates']['list'] === null) {
                    $collection['templates']['list'] = $provider['templates']['list'];
                }

                if (!isset($collection['templates']['item']) || $collection['templates']['item'] === null) {
                    $collection['templates']['item'] = $provider['templates']['item'];
                }

                if (!isset($collection['templates']['error']) || $collection['templates']['error'] === null) {
                    $collection['templates']['error'] = $provider['templates']['error'];
                }

                if (!isset($collection['templates']['not_found']) || $collection['templates']['not_found'] === null) {
                    $collection['templates']['not_found'] = $provider['templates']['not_found'];
                }
            }
        }

        return $providers;
    }

}