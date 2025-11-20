<?php 
namespace OSW3\Api\Resolver\Endpoint\Route;

use OSW3\Api\Controller\Crud\ReadController;
use OSW3\Api\Controller\Crud\CreateController;
use OSW3\Api\Controller\Crud\DeleteController;
use OSW3\Api\Controller\Crud\UpdateController;
use OSW3\Api\Controller\Crud\IndexController;

final class ControllerResolver
{
    public static function resolve(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint)  {

                    if (empty($endpoint['route']['controller'])) {
                        $endpoint['route']['controller'] = match (strtolower($endpointName)) {
                            'index', 'list'         => IndexController::class . '::execute',
                            'add', 'create', 'post' => CreateController::class . '::execute',
                            'read', 'show'          => ReadController::class . '::execute',
                            'put', 'update', 'edit' => UpdateController::class . '::execute',
                            'patch'                 => UpdateController::class . '::execute',
                            'delete'                => DeleteController::class . '::execute',
                            default                 => null,
                        };
                    }
                }
            }
        }

        return $providers;
    }
}