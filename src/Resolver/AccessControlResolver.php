<?php
namespace OSW3\Api\Resolver;

use OSW3\Api\Enum\MergeStrategy;
use OSW3\Api\Service\ContextService;

final class AccessControlResolver
{
    private const SEGMENTS = [
        ContextService::SEGMENT_COLLECTION,
    ];

    public static function execute(array &$config): array 
    {
        foreach ($config['providers'] as &$provider) {

            $providerMerge = $provider['access_control']['merge'] ?? MergeStrategy::APPEND->value;
            $providerRoles = $provider['access_control']['roles'] ?? [];
            $providerVoter = $provider['access_control']['voter'] ?? null;

            foreach (static::SEGMENTS as $segment) {

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


        // ---- Merging roles ----

        foreach ($config['providers'] as &$provider) {
            $provider['access_control']['roles'] = static::mergeRoles([
                $provider['access_control']['roles'],
            ], $provider['access_control']['merge']);

            foreach (static::SEGMENTS as $segment) {
                foreach ($provider[$segment] as &$collection) {
                    $collection['access_control']['roles'] = static::mergeRoles([
                        $provider['access_control']['roles'],
                        $collection['access_control']['roles'],
                    ], $collection['access_control']['merge']);

                    foreach ($collection['endpoints'] as &$endpoint) {
                        $endpoint['access_control']['roles'] = static::mergeRoles([
                            $provider['access_control']['roles'],
                            $collection['access_control']['roles'],
                            $endpoint['access_control']['roles']
                        ], $endpoint['access_control']['merge']);
                    }
                }
            }
        }

        return $config;
    }

    /**
     * Merge roles based on strategy
     * e.g.: mergeRoles([['ROLE_ADMIN'], ['ROLE_USER']], 'append') => ['ROLE_ADMIN', 'ROLE_USER']
     * 
     * @param array<int, array<string>> $rolesList
     * @param string $strategy
     * @return array<string>
     */
    private static function mergeRoles(array $rolesList, string $strategy): array
    {
        $mergedRoles = [];

        foreach ($rolesList as $roles) {
            if (!is_array($roles)) {
                continue;
            }

            switch ($strategy) {
                case MergeStrategy::REPLACE->value:
                    $mergedRoles = $roles;
                    break;

                case MergeStrategy::PREPEND->value:
                    $mergedRoles = array_merge($roles, $mergedRoles);
                    break;

                case MergeStrategy::APPEND->value:
                default:
                    $mergedRoles = array_merge($mergedRoles, $roles);
                    break;
            }
        }

        return array_values(array_unique($mergedRoles));
    }
}