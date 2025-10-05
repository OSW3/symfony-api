<?php 
namespace OSW3\Api\Validator;

use Doctrine\ORM\Mapping as ORM;
use ReflectionClass;

final class EntityValidator
{
    /**
     * Check if a given class is a valid Doctrine entity.
     */
    public static function isValid(string $entityClass): bool
    {
        // 1. La classe doit exister
        if (!class_exists($entityClass)) {
            return false;
        }

        $reflection = new ReflectionClass($entityClass);

        // 2. Vérifie l'attribut #[ORM\Entity]
        foreach ($reflection->getAttributes(ORM\Entity::class) as $attr) {
            return true;
        }

        // 3. Vérifie l'annotation @ORM\Entity (legacy)
        $docComment = $reflection->getDocComment();
        if ($docComment && preg_match('/@ORM\\\\Entity/', $docComment)) {
            return true;
        }

        // 4. Sinon, ce n’est pas une entité Doctrine valide
        return false;
    }

    /**
     * Validate an array of entity classes.
     *
     * @param string[] $entities List of FQCN entity classes.
     * @return string[] List of invalid entity class names (empty array if all are valid).
     */
    public static function validateClassesExist(array $entities): array
    {
        $invalid = [];

        foreach ($entities as $entityClass) {
            if (!self::isValid($entityClass)) {
                $invalid[] = $entityClass;
            }
        }

        return $invalid;
    }
}
