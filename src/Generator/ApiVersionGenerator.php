<?php 
namespace OSW3\Api\Generator;

final class ApiVersionGenerator
{
    public static function generate(array $providers): array
    {
        $usedVersions = [];

        // Première passe : enregistrer les versions déjà définies
        foreach ($providers as $config) {
            if (!empty($config['version'])) {
                $usedVersions[] = $config['version'];
            }
        }

        // Deuxième passe : attribuer automatiquement les versions manquantes
        foreach ($providers as $name => &$config) {
            if (empty($config['version'])) {
                $n = 1;
                // Cherche le premier numéro libre
                while (in_array('v' . $n, $usedVersions, true)) {
                    $n++;
                }
                $config['version'] = 'v' . $n;
                $usedVersions[] = $config['version']; // marquer comme utilisé
            } else {
                // Si version définie ET déjà prise par un autre, on cherche la suivante libre
                if (count(array_keys($usedVersions, $config['version'], true)) > 1) {
                    $n = 1;
                    while (in_array('v' . $n, $usedVersions, true)) {
                        $n++;
                    }
                    $config['version'] = 'v' . $n;
                    $usedVersions[] = $config['version'];
                }
            }
        }

        return $providers;
    }
}