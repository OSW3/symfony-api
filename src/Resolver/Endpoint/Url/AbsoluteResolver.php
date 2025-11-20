<?php 
namespace OSW3\Api\Resolver\Endpoint\Url;

final class AbsoluteResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {

                if (!isset($collection['url']['absolute']) || $collection['url']['absolute'] === null) 
                {
                    $collection['url']['absolute'] = $provider['url']['absolute'];
                }

            }
        }

        return $providers;
    }
}