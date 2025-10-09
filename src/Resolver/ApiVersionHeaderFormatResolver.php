<?php 
namespace OSW3\Api\Resolver;

final class ApiVersionHeaderFormatResolver
{
    public static function resolve(array &$providers): array
    {
        foreach ($providers as $name => &$config) {
            $vendor = $config['vendor'];
            $versionNumber = $config['version']['number'];
            $versionPrefix = $config['version']['prefix'];
            $fullVersion = "{$versionPrefix}{$versionNumber}";
            $config['version']['header_format'] = preg_replace("/{vendor}/", $vendor, $config['version']['header_format']);
            $config['version']['header_format'] = preg_replace("/{version}/", $fullVersion, $config['version']['header_format']);
        }

        return $providers;
    }
}