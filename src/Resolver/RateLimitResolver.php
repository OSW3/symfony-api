<?php 
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\ContextService;

final class RateLimitResolver
{
    private const SEGMENTS = [
        ContextService::SEGMENT_COLLECTION,
    ];
    
    public static function execute(array &$config): array
    {
        foreach ($config['providers'] as &$provider) {

            $providerEnabled = $provider['rate_limit']['enabled'] ?? false;
            $providerLimit = $provider['rate_limit']['limit'] ?? '100/hour';
            $providerByRole = $provider['rate_limit']['by_role'] ?? [];
            $providerByUser = $provider['rate_limit']['by_user'] ?? [];
            $providerByIp = $provider['rate_limit']['by_ip'] ?? [];
            $providerByApplication = $provider['rate_limit']['by_application'] ?? [];
            $providerIncludeHeaders = $provider['rate_limit']['include_headers'] ?? true;


            foreach (static::SEGMENTS as $segment) {

                // Security: missing segment
                if (empty($provider[$segment]) || !is_array($provider[$segment])) {
                    continue;
                }


                // ---- Collections ----

                foreach ($provider[$segment] as &$collection) {

                    // Is Enabled

                    if ($collection['rate_limit']['enabled'] === null) {
                        $collection['rate_limit']['enabled'] = $providerEnabled;
                    }


                    // Limit

                    if ($collection['rate_limit']['limit'] === null) {
                        $collection['rate_limit']['limit'] = $providerLimit;
                    }


                    // By Role 
                    
                    if (
                        !isset($collection['rate_limit']['by_role']) ||
                        (empty($collection['rate_limit']['by_role']) && !empty($provider['rate_limit']['by_role']))
                    ) {
                        $collection['rate_limit']['by_role'] = $providerByRole;
                    }


                    // By User
                    
                    if (
                        !isset($collection['rate_limit']['by_user']) ||
                        (empty($collection['rate_limit']['by_user']) && !empty($provider['rate_limit']['by_user']))
                    ) {
                        $collection['rate_limit']['by_user'] = $providerByUser;
                    }


                    // By IP
                    
                    if (
                        !isset($collection['rate_limit']['by_ip']) ||
                        (empty($collection['rate_limit']['by_ip']) && !empty($provider['rate_limit']['by_ip']))
                    ) {
                        $collection['rate_limit']['by_ip'] = $providerByIp;
                    }


                    // By Application

                    if (
                        !isset($collection['rate_limit']['by_application']) ||
                        (empty($collection['rate_limit']['by_application']) && !empty($provider['rate_limit']['by_application']))
                    ) {
                        $collection['rate_limit']['by_application'] = $providerByApplication;
                    }


                    // Include Headers

                    if ($collection['rate_limit']['include_headers'] === null) {
                        $collection['rate_limit']['include_headers'] = $providerIncludeHeaders;
                    }


                    // ---- Endpoints ----

                    $collectionEnabled = $collection['rate_limit']['enabled'];
                    $collectionLimit = $collection['rate_limit']['limit'];
                    $collectionByRole = $collection['rate_limit']['by_role'];
                    $collectionByUser = $collection['rate_limit']['by_user'];
                    $collectionByIp = $collection['rate_limit']['by_ip'];
                    $collectionByApplication = $collection['rate_limit']['by_application'];
                    $collectionIncludeHeaders = $collection['rate_limit']['include_headers'];

                    foreach ($collection['endpoints'] as $endpointName => &$endpoint) {

                        if (!is_array($endpoint)) {
                            continue;
                        }

                        // Is Enabled

                        if ($endpoint['rate_limit']['enabled'] === null) {
                            $endpoint['rate_limit']['enabled'] = $collectionEnabled;
                        }


                        // Limit

                        if ($endpoint['rate_limit']['limit'] === null) {
                            $endpoint['rate_limit']['limit'] = $collectionLimit;
                        }


                        // By Role 
                        
                        if (
                            !isset($endpoint['rate_limit']['by_role']) ||
                            (empty($endpoint['rate_limit']['by_role']) && !empty($collection['rate_limit']['by_role']))
                        ) {
                            $endpoint['rate_limit']['by_role'] = $collectionByRole;
                        }


                        // By User
                        
                        if (
                            !isset($endpoint['rate_limit']['by_user']) ||
                            (empty($endpoint['rate_limit']['by_user']) && !empty($collection['rate_limit']['by_user']))
                        ) {
                            $endpoint['rate_limit']['by_user'] = $collectionByUser;
                        }


                        // By IP
                        
                        if (
                            !isset($endpoint['rate_limit']['by_ip']) ||
                            (empty($endpoint['rate_limit']['by_ip']) && !empty($collection['rate_limit']['by_ip']))
                        ) {
                            $endpoint['rate_limit']['by_ip'] = $collectionByIp;
                        }


                        // By Application

                        if (
                            !isset($endpoint['rate_limit']['by_application']) ||
                            (empty($endpoint['rate_limit']['by_application']) && !empty($collection['rate_limit']['by_application']))
                        ) {
                            $endpoint['rate_limit']['by_application'] = $collectionByApplication;
                        }


                        // Include Headers

                        if ($endpoint['rate_limit']['include_headers'] === null) {
                            $endpoint['rate_limit']['include_headers'] = $collectionIncludeHeaders;
                        }

                    }
                }
            }
        }

        return $config;
    }
}