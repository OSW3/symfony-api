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
                    $collection['name'] = strtolower(self::pluralize($className));

                    // if (str_ends_with($className, 's')) {
                    //     $collection['name'] = strtolower($className);
                    // } else {
                    //     $collection['name'] = strtolower($className) . 's';
                    // }
                }
            }
        }

        return $providers;
    }


    private static function pluralize(string $word): string
    {
        $word = strtolower($word);
        
        // Exceptions courantes (mots irréguliers)
        $irregulars = [
            'person' => 'people',
            'man' => 'men',
            'woman' => 'women',
            'child' => 'children',
            'tooth' => 'teeth',
            'foot' => 'feet',
            'mouse' => 'mice',
            'goose' => 'geese',
        ];

        if (isset($irregulars[$word])) {
            return $irregulars[$word];
        }

        // Mots se terminant par s, x, z, ch, sh -> ajouter "es"
        if (preg_match('/(s|x|z|ch|sh)$/i', $word)) {
            return $word . 'es';
        }

        // Mots se terminant par consonne + y -> remplacer y par "ies"
        if (preg_match('/[^aeiou]y$/i', $word)) {
            return substr($word, 0, -1) . 'ies';
        }

        // Mots se terminant par f ou fe -> remplacer par "ves"
        if (preg_match('/(f|fe)$/i', $word)) {
            return preg_replace('/(f|fe)$/i', 'ves', $word);
        }

        // Mots se terminant par consonne + o -> ajouter "es"
        if (preg_match('/[^aeiou]o$/i', $word)) {
            return $word . 'es';
        }

        // Cas général -> ajouter "s"
        return $word . 's';
    }
}