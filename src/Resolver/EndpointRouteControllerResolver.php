<?php 
namespace OSW3\Api\Resolver;

use OSW3\Api\Controller\DefaultController;

final class EndpointRouteControllerResolver
{
    public static function resolve(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint)  {

                    if (empty($endpoint['route']['controller'])) {
                        $endpoint['route']['controller'] = match (strtolower($endpointName)) {
                            'index', 'list'         => DefaultController::class . '::index',
                            'add', 'create', 'post' => DefaultController::class . '::create',
                            'read', 'show'          => DefaultController::class . '::read',
                            'put', 'update', 'edit' => DefaultController::class . '::update',
                            'patch'                 => DefaultController::class . '::update',
                            'delete'                => DefaultController::class . '::delete',
                            default                 => null,
                        };
                    }
                }
            }
        }

        return $providers;
    }
}