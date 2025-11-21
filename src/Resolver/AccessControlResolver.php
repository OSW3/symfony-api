<?php
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\ContextService;

final class AccessControlResolver
{
    public static function execute(array &$providers): array 
    {
        // Segments to treat
        $segments = [
            ContextService::SEGMENT_COLLECTION,
        ];
        
        foreach ($providers as &$provider) {

            $providerMerge = $provider['access_control']['merge'] ?? 'append';
            $providerRoles = $provider['access_control']['roles'] ?? [];
            $providerVoter = $provider['access_control']['voter'] ?? null;

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

                    
                    // Merge

                    if ($collection['access_control']['merge'] === null) {
                        $collection['access_control']['merge'] = $providerMerge;
                    }

                    
                    // Roles

                    if (
                        !isset($collection['access_control']['roles']) ||
                        (empty($collection['access_control']['roles']) && !empty($providerRoles))
                    ) {
                        $collection['access_control']['roles'] = $providerRoles;
                    }

                    
                    // Voter

                    if ($collection['access_control']['voter'] === null) {
                        $collection['access_control']['voter'] = $providerVoter;
                    }



                    // ---- Endpoints ----

                    // Skip authentication segment
                    if ($segment === ContextService::SEGMENT_AUTHENTICATION) {
                        continue;
                    }

                    $collectionMerge = $collection['access_control']['merge'];
                    $collectionRoles = $collection['access_control']['roles'];
                    $collectionVoter = $collection['access_control']['voter'];

                    foreach ($collection['endpoints'] as $endpointName => &$endpoint) {

                        if (!is_array($endpoint)) {
                            continue;
                        }

                    
                        // Merge

                        if ($endpoint['access_control']['merge'] === null) {
                            $endpoint['access_control']['merge'] = $collectionMerge;
                        }

                        
                        // Roles

                        if (
                            !isset($endpoint['access_control']['roles']) ||
                            (empty($endpoint['access_control']['roles']) && !empty($collectionRoles))
                        ) {
                            $endpoint['access_control']['roles'] = $collectionRoles;
                        }

                        
                        // Voter

                        if ($endpoint['access_control']['voter'] === null) {
                            $endpoint['access_control']['voter'] = $collectionVoter;
                        }
                    }
                }
            }
        }

        return $providers;
    }
}