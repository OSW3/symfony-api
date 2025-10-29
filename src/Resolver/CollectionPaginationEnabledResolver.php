<?php 
namespace OSW3\Api\Resolver;

final class CollectionPaginationEnabledResolver
{
    public static function default(array &$providers): array
    {
        foreach ($providers as &$provider) {
            $providerPagination = $provider['pagination']['enabled'] ?? true;

            foreach ($provider['collections'] as &$collection) {
                $value = $collection['pagination']['enabled'] ?? null;
                
                if ($value === null) {
                    $collection['pagination']['enabled'] = $providerPagination;
                }
            }
        }

        return $providers;
    }
}