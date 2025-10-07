<?php 
namespace OSW3\Api\Resolver;

final class CollectionNameResolver
{
    public static function resolve(array &$providers): array 
    {
        foreach ($providers as &$provider) {
            foreach ($provider['collections'] as $entity => &$collection) {

                if (empty($collection['name'])) {
                    $className = (new \ReflectionClass($entity))->getShortName();

                    if (str_ends_with($className, 's')) {
                        $collection['name'] = strtolower($className);
                    } else {
                        $collection['name'] = strtolower($className) . 's';
                    }
                }
            }
        }

        return $providers;
    }
}