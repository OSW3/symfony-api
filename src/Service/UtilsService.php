<?php 
namespace OSW3\Api\Service;

use DateTime;
use DateTimeInterface;

final class UtilsService
{
    /**
     * Convert string to camelCase (e.g.:: 'first_name' => 'FirstName')
     */
    public static function camelize(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string)));
    }


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

}