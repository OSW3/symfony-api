<?php 
namespace OSW3\Api\Resolver\Provider\Route;

final class PrefixResolver
{
    public static function resolve(array &$providers): array
    {
        foreach ($providers as &$provider) {
            $provider['routes']['prefix'].= static::version($provider);
        }

        return $providers;
    }

    private static function version(array $provider): string 
    {
        if ($provider['version']['location'] !== 'path') {
            return "";
        }

        $number = $provider['version']['number'];
        $prefix = $provider['version']['prefix'];

        return "/{$prefix}{$number}";
    }
}