<?php 
namespace OSW3\Api\Generator;

final class CollectionNameGenerator
{
    public static function generate(array $collections): array 
    {
        foreach ($collections as $entity => &$collection)
        {
            if (empty($collection['name'])) {
                $className = (new \ReflectionClass($entity))->getShortName();

                if (str_ends_with($className, 's')) {
                    $collection['name'] = strtolower($className);
                } else {
                    $collection['name'] = strtolower($className) . 's';
                }
            }

        }

        return $collections;
    }
}