<?php 
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\ContextService;

final class TemplatesResolver
{
    // Segments to treat
    private const SEGMENTS = [
        ContextService::SEGMENT_AUTHENTICATION,
        ContextService::SEGMENT_COLLECTION,
    ];

    public static function execute(array &$config): array 
    {
        foreach ($config['providers'] as &$provider) {
            foreach (static::SEGMENTS as $segment) {

                if (empty($provider[$segment]) || !is_array($provider[$segment])) {
                    continue;
                }

                foreach ($provider[$segment] as &$collection) {
                    
                    foreach ($collection['templates'] as $template => &$value) {
                        if (!isset($value) || $value === null) {
                            $value = $provider['templates'][$template];
                        }
                    }


                    // Skip authentication segment
                    if ($segment === ContextService::SEGMENT_AUTHENTICATION) {
                        continue;
                    }

                    foreach ($collection['endpoints'] as &$endpoint) {

                        if (!is_array($endpoint)) {
                            continue;
                        }

                        foreach ($endpoint['templates'] as $template => &$value) {
                            if (!isset($value) || $value === null) {
                                $value = $collection['templates'][$template];
                            }
                        }
                    }
                }
            }
        }

        return $config;
    }
}