<?php 
namespace OSW3\Api\Resolver;

final class EndpointPaginationEnabledResolver
{
    public static function default(array &$providers): array
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as &$collection) {
                $collectionPagination = $collection['pagination']['enabled'] ?? ($provider['pagination']['enabled'] ?? true);

                foreach ($collection['endpoints'] as &$endpoint) {
                    $value = $endpoint['pagination']['enabled'] ?? null;

                    if ($value === null) {
                        $endpoint['pagination']['enabled'] = $collectionPagination;
                    }
                }
            }
        }

        return $providers;
    }
}