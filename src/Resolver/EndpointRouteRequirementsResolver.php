<?php 
namespace OSW3\Api\Resolver;

final class EndpointRouteRequirementsResolver
{
    public static function resolve(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint)  {

                    if (
                        empty($endpoint['route']['requirements']) && 
                        in_array(strtolower($endpointName), ['edit','delete','patch','put','read','show','update'], true  )
                    ) {
                        $endpoint['route']['requirements'] = ['id' => '\d+|[\w-]+'];
                    }

                }
            }
        }

        return $providers;
    }
}