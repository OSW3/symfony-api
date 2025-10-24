<?php 
namespace OSW3\Api\Resolver;

final class EndpointUrlPropertyResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {

                if (!isset($collection['url']['property']) || $collection['url']['property'] === null) 
                {
                    $collection['url']['property'] = $provider['url']['property'];
                }

            }
        }

        return $providers;
    }
}