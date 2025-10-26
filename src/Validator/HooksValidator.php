<?php
namespace OSW3\Api\Validator;

final class HooksValidator
{
    /**
     * Validate hooks array.
     *
     * @param array $hooks Expected structure: ['before' => [...], 'after' => [...]]
     * @return bool True if all hooks are valid, false otherwise
     */
    public static function validate(array $hooks): bool
    {
        foreach (['before', 'after', 'around', 'on_success', 'on_error', 'on_complete'] as $key) {
            if (!isset($hooks[$key])) {
                continue;
            }

            foreach ($hooks[$key] as $hook) {
                if (!self::isCallable($hook)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if a hook is a valid callable.
     *
     * @param string|array $hook String like 'Class::method' or callable array [Class, method]
     */
    private static function isCallable($hook): bool
    {
        // String like 'Class::method'
        if (is_string($hook) && str_contains($hook, '::')) {
            [$class, $method] = explode('::', $hook, 2);
            return class_exists($class) && method_exists($class, $method);
        }

        // Regular PHP callable
        return is_callable($hook);
    }
}
