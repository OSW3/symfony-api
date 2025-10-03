<?php 
namespace OSW3\Api\Validator;

final class EntityValidator
{
    public static function validateClassesExist(array $v): bool
    {
        foreach (array_keys($v) as $class) {
            if (!class_exists($class)) {
                return true; // au moins une n'existe pas
            }
        }
        return false;
    }
}
