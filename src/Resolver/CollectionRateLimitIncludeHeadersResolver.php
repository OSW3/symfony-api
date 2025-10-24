<?php 
namespace OSW3\Api\Resolver;

final class CollectionRateLimitIncludeHeadersResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {

                if (!isset($collection['rate_limit']['include_headers'])) {
                    $collection['rate_limit']['include_headers'] = $provider['rate_limit']['include_headers'] ?? false;
                }

            }
        }

        return $providers;
    }

}