<?php
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\ContextService;

final class SerializationResolver
{
    public static function execute(array &$providers): array 
    {
        // Segments to treat
        $segments = [
            ContextService::SEGMENT_AUTHENTICATION,
            ContextService::SEGMENT_COLLECTION,
        ];
        
        foreach ($providers as &$provider) {

            $providerGroups = [];
            $providerIgnore = $provider['serialization']['ignore'] ?? [];
            $providerDatetime = $provider['serialization']['datetime'] ?? [];
            $providerSkipNull = $provider['serialization']['skip_null'] ?? false;
            $providerTransformer = null;

            foreach ($segments as $segment) {

                // Security: missing segment
                if (empty($provider[$segment]) || !is_array($provider[$segment])) {
                    continue;
                }


                // ---- Collections ----

                foreach ($provider[$segment] as &$collection) {

                    // Check collection is array
                    if (!is_array($collection)) {
                        continue;
                    }
                    
                    // Groups
                    
                    if (
                        !isset($collection['serialization']['groups']) ||
                        (empty($collection['serialization']['groups']) && !empty($providerGroups))
                    ) {
                        $collection['serialization']['groups'] = $providerGroups;
                    }
                    
                    
                    // Ignore
                    
                    if (
                        !isset($collection['serialization']['ignore']) ||
                        (empty($collection['serialization']['ignore']) && !empty($providerIgnore))
                    ) {
                        $collection['serialization']['ignore'] = $providerIgnore;
                    }
                    
                    
                    // Transformer
                    
                    if ($collection['serialization']['transformer'] === null) {
                        $collection['serialization']['transformer'] = $providerTransformer;
                    }



                    // ---- Endpoints ----

                    // Skip authentication segment
                    if ($segment === ContextService::SEGMENT_AUTHENTICATION) {
                        continue;
                    }

                    $collectionGroups = $collection['serialization']['groups'];
                    $collectionIgnore = $collection['serialization']['ignore'];
                    $collectionTransformer = $collection['serialization']['transformer'];

                    foreach ($collection['endpoints'] as $endpointName => &$endpoint) {

                        if (!is_array($endpoint)) {
                            continue;
                        }
                    
                        // Groups
                        
                        if (
                            !isset($endpoint['serialization']['groups']) ||
                            (empty($endpoint['serialization']['groups']) && !empty($collectionGroups))
                        ) {
                            $endpoint['serialization']['groups'] = $collectionGroups;
                        }
                        
                        
                        // Ignore
                        
                        if (
                            !isset($endpoint['serialization']['ignore']) ||
                            (empty($endpoint['serialization']['ignore']) && !empty($collectionIgnore))
                        ) {
                            $endpoint['serialization']['ignore'] = $collectionIgnore;
                        }
                        
                        
                        // Transformer
                        
                        if ($endpoint['serialization']['transformer'] === null) {
                            $endpoint['serialization']['transformer'] = $collectionTransformer;
                        }

                    }


                }

            }

        }

        return $providers;
    }
}