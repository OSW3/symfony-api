<?php 
namespace OSW3\Api\Resolver\Collection;

final class TemplatesResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {

                if (!isset($collection['templates']['list']) || $collection['templates']['list'] === null) {
                    $collection['templates']['list'] = $provider['templates']['list'];
                }

                if (!isset($collection['templates']['single']) || $collection['templates']['single'] === null) {
                    $collection['templates']['single'] = $provider['templates']['single'];
                }

                if (!isset($collection['templates']['delete']) || $collection['templates']['delete'] === null) {
                    $collection['templates']['delete'] = $provider['templates']['delete'];
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