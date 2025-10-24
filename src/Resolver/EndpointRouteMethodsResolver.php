<?php 
namespace OSW3\Api\Resolver;

use Symfony\Component\HttpFoundation\Request;

final class EndpointRouteMethodsResolver
{
    public static function resolve(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $collectionName => &$collection) {
                foreach ($collection['endpoints'] as $endpointName => &$endpoint)  {

                    if (empty($endpoint['route']['methods'])) {
                        $endpoint['route']['methods'] = match (strtolower($endpointName)) {
                            'add', 'create', 'post'         => [Request::METHOD_POST],
                            'put', 'update', 'edit'         => [Request::METHOD_PUT],
                            'patch'                         => [Request::METHOD_PATCH],
                            'delete'                        => [Request::METHOD_DELETE],
                            'index', 'list', 'read', 'show' => [Request::METHOD_GET, Request::METHOD_HEAD],
                            default                         => [Request::METHOD_GET, Request::METHOD_HEAD],
                        };
                    }
                }
            }
        }

        return $providers;
    }
}