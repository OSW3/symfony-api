<?php 
namespace OSW3\Api\Resolver;

final class EndpointRouteOptionsResolver
{
    public static function resolve(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint)  {

                    if (
                        empty($endpoint['route']['options']) && 
                        in_array(strtolower($endpointName), ['edit','delete','patch','put','read','show','update'], true  )
                    ) {
                        $endpoint['route']['options'] = ['id'];
                    }

                }
            }
        }

        return $providers;
    }
}