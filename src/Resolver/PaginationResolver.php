<?php 
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\ContextService;

final class PaginationResolver
{
    public static function execute(array &$config): array
    {
        foreach ($config['providers'] as &$provider) {
            $providerEnabled = $provider['pagination']['enabled'] ?? true;
            $providerLimit = $provider['pagination']['limit'] ?? 10;
            $providerMaxLimit = $provider['pagination']['max_limit'] ?? 100;
            $providerAllowLimitOverride = $provider['pagination']['allow_limit_override'] ?? true;

        
            if (empty($provider[ContextService::SEGMENT_COLLECTION]) || !is_array($provider[ContextService::SEGMENT_COLLECTION])) {
                continue;
            }


            // ---- Collections ----

            foreach ($provider[ContextService::SEGMENT_COLLECTION] as &$collection) {

                // Check collection is array
                if (!is_array($collection)) {
                    continue;
                }


                // Is Enabled

                if (!isset($collection['pagination']['enabled']) || $collection['pagination']['enabled'] === null) {
                    $collection['pagination']['enabled'] = $providerEnabled;
                }


                // Limit

                if (
                    !isset($collection['pagination']['limit']) || 
                    $collection['pagination']['limit'] === null || 
                    $collection['pagination']['limit'] <= -1
                ){
                    $collection['pagination']['limit'] = $providerLimit;
                }


                // Max Limit

                if (
                    !isset($collection['pagination']['max_limit']) || 
                    $collection['pagination']['max_limit'] === null || 
                    $collection['pagination']['max_limit'] <= -1
                ){
                    $collection['pagination']['max_limit'] = $providerMaxLimit;
                }


                // Allow Override Limit

                if (!isset($collection['pagination']['allow_limit_override']) || $collection['pagination']['allow_limit_override'] === null) 
                {
                    $collection['pagination']['allow_limit_override'] = $providerAllowLimitOverride;
                }
                
                
                // Parameters
                    // page
                    // limit



                // ---- Endpoints ----

                $collectionEnabled = $collection['pagination']['enabled'];
                $collectionLimit = $collection['pagination']['limit'];
                $collectionMaxLimit = $collection['pagination']['max_limit'];
                $collectionAllowLimitOverride = $collection['pagination']['allow_limit_override'];

                foreach ($collection['endpoints'] as $endpointName => &$endpoint) {

                    // dump(array_key_exists('enabled', $endpoint['pagination']));
                    if (in_array(strtolower($endpointName), ['edit','delete','patch','put','read','show','update'], true  )) {
                        $endpoint['pagination'] = null;
                        continue;
                    }

                    // Is Enabled

                    if (
                        !array_key_exists('enabled', $endpoint['pagination']) || 
                        $endpoint['pagination']['enabled'] === null
                    ) {
                        $endpoint['pagination']['enabled'] = $collectionEnabled;
                    }


                    // Limit

                    if (
                        !isset($endpoint['pagination']['limit']) || 
                        $endpoint['pagination']['limit'] === null || 
                        $endpoint['pagination']['limit'] <= -1
                    ){
                        $endpoint['pagination']['limit'] = $collectionLimit;
                    }


                    // Max Limit

                    if (
                        !isset($endpoint['pagination']['max_limit']) || 
                        $endpoint['pagination']['max_limit'] === null || 
                        $endpoint['pagination']['max_limit'] <= -1
                    ){
                        $endpoint['pagination']['max_limit'] = $collectionMaxLimit;
                    }


                    // Allow Override Limit

                    if ($endpoint['pagination']['allow_limit_override'] === null) 
                    {
                        $endpoint['pagination']['allow_limit_override'] = $collectionAllowLimitOverride;
                    }
                }

            }
        }

        return $config;
    }
}