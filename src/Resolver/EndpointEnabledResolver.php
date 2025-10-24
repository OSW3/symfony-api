<?php 
namespace OSW3\Api\Resolver;

final class EndpointEnabledResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint)  {


                    // dump($endpoint['enabled']);
                    // if (!isset($endpoint['enabled'])) {
                    //     $endpoint['enabled'] = true;
                    // }

                }
            }
        }

        return $providers;
    }
}