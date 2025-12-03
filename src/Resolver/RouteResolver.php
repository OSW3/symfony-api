<?php 
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\ContextService;
use OSW3\Api\Controller\Crud\ReadController;
use OSW3\Api\Controller\Crud\IndexController;
use OSW3\Api\Controller\Crud\CreateController;
use OSW3\Api\Controller\Crud\DeleteController;
use OSW3\Api\Controller\Crud\UpdateController;
use OSW3\Api\Enum\Route\DefaultEndpoint;

final class RouteResolver
{
    // Segments to treat
    private const SEGMENTS = [
        ContextService::SEGMENT_AUTHENTICATION,
        ContextService::SEGMENT_COLLECTION,
    ];

    public static function execute(array &$config): array
    {
        foreach ($config['providers'] as &$provider) {
            $version = static::version($provider);

            $providerPattern  = $provider['routes']['pattern'];
            $providerPrefix   = DIRECTORY_SEPARATOR. trim($provider['routes']['prefix'], DIRECTORY_SEPARATOR);
            $providerPrefix  .= DIRECTORY_SEPARATOR . $version;
            $providerHosts    = $provider['routes']['hosts'];
            $providerSchemes  = $provider['routes']['schemes'];

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


                    // Pattern

                    if (empty(trim($collection['routes']['pattern']))) 
                    {
                        $collection['routes']['pattern'] = $providerPattern;
                    }
                    

                    // Prefix

                    if (empty(trim($collection['routes']['prefix']))) 
                    {
                        $collection['routes']['prefix'] = $providerPrefix;
                    }
                    $collection['routes']['prefix'] = str_replace(
                        '{version}',
                        $version,
                        $collection['routes']['prefix']
                    );
                    $collection['routes']['prefix'] = str_replace(
                        '{collection}',
                        $collection['name'],
                        $collection['routes']['prefix']
                    );
                    
                    if ($segment === ContextService::SEGMENT_AUTHENTICATION) {
                        $collection['routes']['prefix'].= DIRECTORY_SEPARATOR . trim($collection['routes']['additional_prefix'], DIRECTORY_SEPARATOR);
                    }
                    
                    $collection['routes']['prefix'] = trim($collection['routes']['prefix'], DIRECTORY_SEPARATOR);


                    // Hosts

                    if (empty($collection['routes']['hosts'])) 
                    {
                        $collection['routes']['hosts'] = $providerHosts;
                    }

                    if (in_array('*', $collection['routes']['hosts'])) 
                    {
                        $collection['routes']['hosts'] = [];
                    }


                    // Schemes

                    if (empty($collection['routes']['schemes'])) 
                    {
                        $collection['routes']['schemes'] = $providerSchemes;
                    }

                    if (in_array('*', $collection['routes']['schemes'])) 
                    {
                        $collection['routes']['schemes'] = [];
                    }



                    // ---- Endpoints ----

                    $collectionPattern = $collection['routes']['pattern'];
                    $collectionPrefix  = $collection['routes']['prefix'];
                    $collectionHosts   = $collection['routes']['hosts'];
                    $collectionSchemes = $collection['routes']['schemes'];

                    foreach ($collection['endpoints'] as $endpointName => &$endpoint)  {

                        // Pattern

                        if (
                            $segment !== ContextService::SEGMENT_AUTHENTICATION &&
                            empty(trim($endpoint['route']['pattern']))
                        ) {
                            $endpoint['route']['pattern'] = $collectionPattern;
                        }


                        // Name

                        if (empty(trim($endpoint['route']['name'])))
                        {
                            $endpoint['route']['name'] = $collectionPattern;
                        }

                        $endpoint['route']['name'] = str_replace(
                            '{version}',
                            $version,
                            $endpoint['route']['name']
                        );
                        $endpoint['route']['name'] = str_replace(
                            '{collection}',
                            $collection['name'],
                            $endpoint['route']['name']
                        );
                        $endpoint['route']['name'] = str_replace(
                            '{action}',
                            $endpointName,
                            $endpoint['route']['name']
                        );


                        // Path

                        if (empty(trim($endpoint['route']['path'])))
                        {
                            $endpoint['route']['path'] = "{$collectionPrefix}/{$collection['name']}"; // "/api/v1/books

                            if ($segment === ContextService::SEGMENT_AUTHENTICATION) {
                                $endpoint['route']['path'].= DIRECTORY_SEPARATOR . trim($endpointName, DIRECTORY_SEPARATOR);
                            }
                        }

                        $endpoint['route']['path'] = str_replace(
                            '{prefix}',
                            $collectionPrefix,
                            $endpoint['route']['path']
                        );
                        $endpoint['route']['path'] = str_replace(
                            '{version}',
                            $version,
                            $endpoint['route']['path']
                        );
                        $endpoint['route']['path'] = str_replace(
                            '{collection}',
                            $collection['name'],
                            $endpoint['route']['path']
                        );
                        $endpoint['route']['path'] = str_replace(
                            '{action}',
                            $endpointName,
                            $endpoint['route']['path']
                        );


                        // Methods

                        if ($segment === ContextService::SEGMENT_COLLECTION) {
                            if (empty($endpoint['route']['methods'])) {
                                $endpoint['route']['methods'] = match (strtolower($endpointName)) {
                                    DefaultEndpoint::ADD->value, 
                                    DefaultEndpoint::CREATE->value, 
                                    DefaultEndpoint::POST->value       => ['POST'],
                                    DefaultEndpoint::PUT->value, 
                                    DefaultEndpoint::UPDATE->value, 
                                    DefaultEndpoint::EDIT->value       => ['PUT'],
                                    DefaultEndpoint::PATCH->value      => ['PATCH'],
                                    DefaultEndpoint::DELETE->value     => ['DELETE'],
                                    DefaultEndpoint::INDEX->value, 
                                    DefaultEndpoint::LIST->value, 
                                    DefaultEndpoint::READ->value       => ['GET', 'HEAD'],
                                    default                     => ['GET', 'HEAD'],
                                };
                            }
                        }

                        // Controller
                        
                        if ($segment === ContextService::SEGMENT_COLLECTION) {
                            if (empty($endpoint['route']['controller'])) {
                                $endpoint['route']['controller'] = match (strtolower($endpointName)) {
                                    DefaultEndpoint::INDEX->value, 
                                    DefaultEndpoint::LIST->value       => IndexController::class . '::execute',
                                    DefaultEndpoint::ADD->value, 
                                    DefaultEndpoint::CREATE->value, 
                                    DefaultEndpoint::POST->value       => CreateController::class . '::execute',
                                    DefaultEndpoint::READ->value       => ReadController::class . '::execute',
                                    DefaultEndpoint::PUT->value, 
                                    DefaultEndpoint::UPDATE->value, 
                                    DefaultEndpoint::EDIT->value       => UpdateController::class . '::execute',
                                    DefaultEndpoint::PATCH->value      => UpdateController::class . '::execute',
                                    DefaultEndpoint::DELETE->value     => DeleteController::class . '::execute',
                                    default                     => null,
                                };
                            }
                        }


                        // Requirements

                        if ($segment === ContextService::SEGMENT_COLLECTION) {
                            if (
                                empty($endpoint['route']['requirements']) && 
                                in_array(strtolower($endpointName), [
                                    DefaultEndpoint::EDIT->value,
                                    DefaultEndpoint::DELETE->value,
                                    DefaultEndpoint::PATCH->value,
                                    DefaultEndpoint::PUT->value,
                                    DefaultEndpoint::READ->value,
                                    DefaultEndpoint::SHOW->value,
                                    DefaultEndpoint::UPDATE->value
                                ], true  )
                            ) {
                                $endpoint['route']['requirements'] = ['id' => '\d+|[\w-]+'];
                            }
                        }


                        // Options

                        if ($segment === ContextService::SEGMENT_COLLECTION) {
                            if (
                                empty($endpoint['route']['options']) && 
                                in_array(strtolower($endpointName), [
                                    DefaultEndpoint::EDIT->value,
                                    DefaultEndpoint::DELETE->value,
                                    DefaultEndpoint::PATCH->value,
                                    DefaultEndpoint::PUT->value,
                                    DefaultEndpoint::READ->value,
                                    DefaultEndpoint::SHOW->value,
                                    DefaultEndpoint::UPDATE->value
                                ], true  )
                            ) {
                                $endpoint['route']['options'] = ['id'];
                            }
                        }


                        // Conditions


                        // Hosts

                        if (empty($endpoint['route']['hosts'])) 
                        {
                            $endpoint['route']['hosts'] = $collectionHosts;
                        }

                        if (in_array('*', $endpoint['route']['hosts'])) 
                        {
                            $endpoint['route']['hosts'] = [];
                        }


                        // Schemes

                        if (empty($endpoint['route']['schemes'])) 
                        {
                            $endpoint['route']['schemes'] = $collectionSchemes;
                        }

                        if (in_array('*', $endpoint['route']['schemes'])) 
                        {
                            $endpoint['route']['schemes'] = [];
                        }

                    }
                }
            }
        }

        return $config;
    }

    private static function version(array $provider): string 
    {
        if ($provider['version']['location'] !== 'path') {
            return "";
        }

        $number = $provider['version']['number'];
        $prefix = $provider['version']['prefix'];

        return "{$prefix}{$number}";
    }
}