<?php 
namespace OSW3\Api\Resolver;

final class ProviderAuthenticationNameResolver
{
    public static function resolve(array &$providers): array
    {
        foreach ($providers as &$config) {
            foreach ($config['authentication'] as $entity => &$options) {
                if (empty($options['name'])) {
                    $className = (new \ReflectionClass($entity))->getShortName();
                    $options['name'] = strtolower($className);
                }
            }
        }

        return $providers;
    }

}