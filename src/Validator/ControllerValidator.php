<?php 
namespace OSW3\Api\Validator;

final class ControllerValidator
{
    public static function isValid(string $controller): bool
    {
        if (!str_contains($controller, '::')) {
            return false;
        }

        [$class, $method] = explode('::', $controller, 2);

        if (!class_exists($class)) {
            return false;
        }

        if (!method_exists($class, $method)) {
            return false;
        }

        return true;
    }
}