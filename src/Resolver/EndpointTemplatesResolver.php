<?php 
namespace OSW3\Api\Resolver;

final class EndpointTemplatesResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {
                foreach ($collection['endpoints'] as &$endpoint) {

                    if (!isset($endpoint['templates']['list']) || $endpoint['templates']['list'] === null) {
                        $endpoint['templates']['list'] = $collection['templates']['list'];
                    }

                    if (!isset($endpoint['templates']['item']) || $endpoint['templates']['item'] === null) {
                        $endpoint['templates']['item'] = $collection['templates']['item'];
                    }

                    if (!isset($endpoint['templates']['error']) || $endpoint['templates']['error'] === null) {
                        $endpoint['templates']['error'] = $collection['templates']['error'];
                    }

                    if (!isset($endpoint['templates']['not_found']) || $endpoint['templates']['not_found'] === null) {
                        $endpoint['templates']['not_found'] = $collection['templates']['not_found'];
                    }
                }

            }
        }

        return $providers;
    }

}