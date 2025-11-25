<?php 
namespace OSW3\Api\Helper;

final class HeaderHelper 
{
    /**
     * Convert a header name to Header-Case format.
     * 
     * @param string $header The header name to convert
     * 
     * @return string The converted header name in Header-Case format
     */
    public static function toHeaderCase(string $header): string 
    {
        // $header = str_replace('-', ' ', strtolower($header));
        // $header = str_replace(' ', '-', ucwords($header));
        $header = str_replace('_', '-', ucwords($header, '-'));
        // $header = str_replace('_', '-', $header);

        return $header;
    }

    /**
     * Convert a header value to a string format suitable for HTTP headers.
     * 
     * @param mixed $value The header value to convert
     * 
     * @return string The converted header value as a string
     */
    public static function toHeaderValue(mixed $value): string 
    {
        $value = is_array($value) ? implode(', ', $value) : $value;
        $value = is_string($value) ? trim($value) : $value;

        return $value;
    }
}