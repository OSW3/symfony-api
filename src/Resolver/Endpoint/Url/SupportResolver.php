<?php 
namespace OSW3\Api\Resolver\Endpoint\Url;

final class SupportResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {

                    if (!isset($collection['url']['support']) || $collection['url']['support'] === null) 
                    {
                        $collection ['url']['support'] = $provider['url']['support'];
                    }

            }
        }

        return $providers;
    }
}