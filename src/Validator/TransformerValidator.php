<?php
namespace OSW3\Api\Validator;

final class TransformerValidator
{
    /**
     * Checks if the given transformer class exists and implements __invoke or a "transform" method.
     */
    public static function isValid(?string $transformer): bool
    {
        if ($transformer === null) {
            return true; // optional
        }

        if (!class_exists($transformer)) {
            return false;
        }

        // Check if class has __invoke() or transform() method
        if (!method_exists($transformer, '__invoke') && !method_exists($transformer, 'transform')) {
            return false;
        }

        return true;
    }

    /**
     * Validate multiple transformers from an array
     */
    public static function validateArray(array $transformers): bool
    {
        foreach ($transformers as $transformer) {
            if (!self::isValid($transformer)) {
                return false;
            }
        }

        return true;
    }
}
