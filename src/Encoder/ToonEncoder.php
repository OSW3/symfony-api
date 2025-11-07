<?php 
declare(strict_types=1);

namespace OSW3\Api\Encoder;

use InvalidArgumentException;

final class ToonEncoder
{
    /**
     * Encode une structure (tableau PHP ou chaîne JSON) au format TOON.
     *
     * Ex:
     * users[2]{id,name,role}:
     *   1,Alice,admin
     *   2,Bob,user
     * active: true
     *
     * @param array|string $input
     */
    public static function encode(array|string $input): string
    {
        $data = is_string($input) ? json_decode($input, true) : $input;

        if (!is_array($data)) {
            throw new InvalidArgumentException('Entrée invalide: fournir un tableau PHP ou une chaîne JSON représentant un objet.');
        }
        if (is_string($input) && json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Chaîne JSON invalide.');
        }

        $lines = [];

        foreach ($data as $key => $value) {
            if (self::isListOfAssocArrays($value)) {
                // Tableau d’objets => table TOON
                $rows = $value;
                $columns = self::collectColumns($rows);
                $lines[] = sprintf('%s[%d]{%s}:', (string)$key, count($rows), implode(',', $columns));

                foreach ($rows as $row) {
                    $values = [];
                    foreach ($columns as $col) {
                        $cell = $row[$col] ?? null;
                        $values[] = self::scalarToString($cell);
                    }
                    $lines[] = '  ' . implode(',', $values);
                }
            } elseif (is_array($value) && self::isAssoc($value)) {
                // Objet simple => une table à une ligne
                $columns = array_keys($value);
                $lines[] = sprintf('%s[1]{%s}:', (string)$key, implode(',', $columns));
                $row = [];
                foreach ($columns as $col) {
                    $row[] = self::scalarToString($value[$col] ?? null);
                }
                $lines[] = '  ' . implode(',', $row);
            } elseif (is_array($value) && array_is_list($value)) {
                // Liste scalaire => liste simple
                $lines[] = sprintf('%s[%d]:', (string)$key, count($value));
                foreach ($value as $item) {
                    $lines[] = '  ' . self::scalarToString($item);
                }
            } else {
                // Scalaire
                $lines[] = sprintf('%s: %s', (string)$key, self::scalarToString($value));
            }
        }

        return implode(PHP_EOL, $lines);
    }

    private static function isAssoc(array $arr): bool
    {
        return !array_is_list($arr);
    }

    private static function isListOfAssocArrays(mixed $value): bool
    {
        if (!is_array($value) || !array_is_list($value) || $value === []) {
            return false;
        }
        foreach ($value as $item) {
            if (!is_array($item) || !self::isAssoc($item)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Collecte les colonnes en préservant l’ordre du premier élément,
     * puis ajoute les nouvelles clés rencontrées dans l’ordre d’apparition.
     */
    private static function collectColumns(array $rows): array
    {
        $columns = [];
        if ($rows === []) {
            return $columns;
        }

        // Clés du premier élément
        foreach (array_keys($rows[0]) as $k) {
            $columns[$k] = true;
        }

        // Ajouter les clés manquantes rencontrées ensuite
        foreach ($rows as $row) {
            foreach ($row as $k => $_) {
                if (!isset($columns[$k])) {
                    $columns[$k] = true;
                }
            }
        }

        return array_keys($columns);
    }

    /**
     * Conversion scalaire vers chaîne TOON (sans guillemets pour strings).
     * - bool: true/false
     * - null: null
     * - number: tel quel
     * - string: tel quel (virgules et retours à la ligne sont compactés).
     * - autres types (array/objet): JSON compact.
     */
    private static function scalarToString(mixed $v): string
    {
        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        }
        if (is_null($v)) {
            return 'null';
        }
        if (is_int($v) || is_float($v)) {
            return (string)$v;
        }
        if (is_string($v)) {
            // Éviter les retours à la ligne et normaliser les espaces
            $s = preg_replace('/\s+/', ' ', $v);
            return str_replace(["\r", "\n"], ' ', $s ?? $v);
        }

        // Fallback: sérialiser en JSON compact
        return rtrim(json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'null');
    }
}