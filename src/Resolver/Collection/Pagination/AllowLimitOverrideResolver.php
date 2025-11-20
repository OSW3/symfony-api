<?php 
namespace OSW3\Api\Resolver\Collection\Pagination;

final class AllowLimitOverrideResolver
{
    public static function default(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {

                if (!isset($collection['pagination']['allow_limit_override']) || $collection['pagination']['allow_limit_override'] === null) 
                {
                    $collection['pagination']['allow_limit_override'] = $provider['pagination']['allow_limit_override'];
                }
                
            }
        }

        return $providers;
    }
}