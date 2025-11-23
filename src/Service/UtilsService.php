<?php 
namespace OSW3\Api\Service;

use DateTime;
use DateTimeInterface;

final class UtilsService
{
    /**
     * Convert a string to CamelCase
     * e.g. my_string-name => MyStringName
     * 
     * @param string $string
     * @return string
     */
    public static function camelize(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string)));
    }

    /**
     * Check if a value is a date (DateTimeInterface, timestamp or Y-m-d string)
     * 
     * @param mixed $value
     * @return bool
     */
    public static function is_date($value): bool
    {
        if ($value instanceof DateTimeInterface) {
            return true;
        }

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return $value > 0 && (bool) @date('Y-m-d', (int)$value);
        }

        if (is_string($value)) {
            $d = DateTime::createFromFormat('Y-m-d', $value);
            return $d && $d->format('Y-m-d') === $value;
        }

        return false;
    }

    /**
     * Convert a date value to an HTTP date string
     * 
     * @param mixed $value
     * @return string|null
     */
    public static function to_http_date($value): ?string
    {
        if (!static::is_date($value)) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            $ts = $value->getTimestamp();
        } elseif (is_int($value) || ctype_digit($value)) {
            $ts = (int)$value;
        } else {
            $dt = DateTime::createFromFormat('Y-m-d', $value);
            $ts = $dt ? $dt->getTimestamp() : null;
        }

        return $ts ? gmdate('D, d M Y H:i:s \G\M\T', $ts) : null;
    }

    /**
     * Pluralize a word
     * 
     * @param string $word
     * @return string
     */
    public static function pluralize(string $word): string
    {
        $w = strtolower($word);

        // ---------------------------------------------------------
        // 1. Irregulars
        // ---------------------------------------------------------
        $irregulars = [
            'person' => 'people',
            'man' => 'men',
            'woman' => 'women',
            'child' => 'children',
            'tooth' => 'teeth',
            'foot' => 'feet',
            'mouse' => 'mice',
            'goose' => 'geese',
            'louse' => 'lice',
            'die' => 'dice',
            'ox' => 'oxen',

            // Common Greek/Latin
            'index' => 'indices',
            'matrix' => 'matrices',
            'axis' => 'axes',
            'crisis' => 'crises',
            'analysis' => 'analyses',
            'thesis' => 'theses',
            'basis' => 'bases',
            'diagnosis' => 'diagnoses',
            'parenthesis' => 'parentheses',
            'ellipsis' => 'ellipses'
        ];

        if (isset($irregulars[$w])) {
            return $irregulars[$w];
        }

        // ---------------------------------------------------------
        // 2. Words that take "ves"
        // (maximal realistic list)
        // ---------------------------------------------------------
        $fToVes = [
            'knife', 'life', 'wife', 'leaf', 'wolf', 'shelf',
            'calf', 'half', 'loaf', 'elf', 'thief', 'self',
            'scarf', // scarf → scarves
            'dwarf'  // dwarf → dwarves (most common form)
        ];

        if (in_array($w, $fToVes, true)) {
            return preg_replace('/fe?$/', 'ves', $w);
        }

        // ---------------------------------------------------------
        // 3. Words ending in "o" that take "es" (enriched list)
        // ---------------------------------------------------------
        $oEs = [
            'potato','tomato','hero','echo','torpedo','veto',
            'mosquito','buffalo','volcano','zero','embargo',
            'cargo','tornado','negro','domino'
        ];

        if (in_array($w, $oEs, true)) {
            return $w . 'es';
        }

        // ---------------------------------------------------------
        // 4. Words ending in "o" that take just "s"
        // ---------------------------------------------------------
        $oS = [
            'photo','piano','halo','memo','solo','cello','video',
            'radio','avocado','taco','kimono','logo','studio',
            'kilo','auto','pro'
        ];

        if (in_array($w, $oS, true)) {
            return $w . 's';
        }

        // ---------------------------------------------------------
        // 5. Words ending in "f"/"fe" that do NOT take "ves"
        // ---------------------------------------------------------
        $noVes = [
            'roof','belief','chef','chief','proof','reef','cliff',
            'handkerchief','brief','safe'
        ];

        if (in_array($w, $noVes, true)) {
            return $w . 's';
        }

        // staff → staffs
        if ($w === 'staff') {
            return 'staffs';
        }

        // ---------------------------------------------------------
        // 6. Latin plurals "us" → "i"
        // ---------------------------------------------------------
        $usToI = [
            'cactus','focus','fungus','nucleus','stimulus','radius',
            'alumnus'
        ];

        if (in_array($w, $usToI, true)) {
            return preg_replace('/us$/', 'i', $w);
        }

        // ---------------------------------------------------------
        // 7. Latin/Greek words ending in "um" → "a"
        // ---------------------------------------------------------
        $umToA = [
            'bacterium','medium','memorandum','curriculum','datum',
            'phenomenon' => 'phenomena'
        ];
        if (isset($umToA[$w])) {
            return $umToA[$w];
        }
        if (preg_match('/um$/', $w) && in_array($w, ['bacterium','medium','curriculum'], true)) {
            return preg_replace('/um$/', 'a', $w);
        }

        // ---------------------------------------------------------
        // 8. Latin words ending in "on" → "a"
        // ---------------------------------------------------------
        $onToA = [
            'phenomenon' => 'phenomena',
            'criterion'  => 'criteria'
        ];
        if (isset($onToA[$w])) {
            return $onToA[$w];
        }

        // ---------------------------------------------------------
        // 9. -is → -es (if not handled above)
        // ---------------------------------------------------------
        if (preg_match('/is$/', $w)) {
            return preg_replace('/is$/', 'es', $w);
        }

        // ---------------------------------------------------------
        // 10. s, x, z, ch, sh → es
        // ---------------------------------------------------------
        if (preg_match('/(s|x|z|ch|sh)$/', $w)) {
            return $w . 'es';
        }

        // ---------------------------------------------------------
        // 11. Consonant + y → ies
        // ---------------------------------------------------------
        if (preg_match('/[^aeiou]y$/', $w)) {
            return substr($w, 0, -1) . 'ies';
        }

        // ---------------------------------------------------------
        // 12. General case → s
        // ---------------------------------------------------------
        return $w . 's';
    }
}