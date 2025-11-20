<?php 
namespace OSW3\Api\Resolver;

use OSW3\Api\Service\UtilsService;
use OSW3\Api\Service\ContextService;

final class NameResolver
{
    public static function execute(array &$providers): array
    {
        foreach ($providers as &$provider) {

            self::resolveAuthenticationNames($provider);
            self::resolveCollectionNames($provider);
        }

        return $providers;
    }

    private static function resolveAuthenticationNames(array &$provider): void
    {
        $segment = ContextService::SEGMENT_AUTHENTICATION;

        if (empty($provider[$segment]) || !is_array($provider[$segment])) {
            return;
        }

        foreach ($provider[$segment] as $entity => $options) {

            if (!is_string($entity) || !class_exists($entity)) {
                continue;
            }

            if (!isset($options['name']) || $options['name'] === null || $options['name'] === '') {
                try {
                    $short = (new \ReflectionClass($entity))->getShortName();
                    $options['name'] = strtolower($short);
                } catch (\ReflectionException $e) {
                    continue;
                }
            }

            $provider[$segment][$entity] = $options;
        }
    }

    private static function resolveCollectionNames(array &$provider): void
    {
        $segment = ContextService::SEGMENT_COLLECTION;

        if (empty($provider[$segment]) || !is_array($provider[$segment])) {
            return;
        }

        foreach ($provider[$segment] as $entity => &$collection) {

            if (!is_string($entity) || !class_exists($entity)) {
                continue;
            }

            if (empty($collection['name'])) {
                try {
                    $short = (new \ReflectionClass($entity))->getShortName();
                    $collection['name'] = strtolower(UtilsService::pluralize($short));
                } catch (\ReflectionException $e) {
                    continue;
                }
            }
        }
        unset($collection);
    }
}