<?php 
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\ContextService;

final class UrlSupportResolver
{
    private const SEGMENTS = [
        ContextService::SEGMENT_AUTHENTICATION,
        ContextService::SEGMENT_COLLECTION,
    ];

    public static function execute(array &$config): array 
    {   
        foreach ($config['providers'] as &$provider) {
            $providerSupport = $provider['url']['support'] ?? true;
            $providerAbsolute = $provider['url']['absolute'] ?? true;
            $providerProperty = $provider['url']['property'] ?? 'url';

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

                    
                    // Has support

                    if ($collection['url']['support'] === null) {
                        $collection['url']['support'] = $providerSupport;
                    }


                    // Is absolute

                    if ($collection['url']['absolute'] === null) {
                        $collection['url']['absolute'] = $providerAbsolute;
                    }


                    // Property

                    if ($collection['url']['property'] === null) {
                        $collection['url']['property'] = $providerProperty;
                    }

                }
            }
        }

        return $config;
    }
}