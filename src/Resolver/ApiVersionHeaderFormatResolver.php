<?php 
namespace OSW3\Api\Resolver;

use Symfony\Component\Filesystem\Path;

final class ApiVersionHeaderFormatResolver
{
    public static function getAppVendor(): ?string
    {
        // $composerFile = Path::join($_SERVER['PWD'] ?? \getcwd(), 'composer.json');
        $composerFile = Path::join(\dirname(__DIR__, 2), 'composer.json');
        $composerJson = file_get_contents($composerFile);
        $composerData = json_decode($composerJson, true);

        $vendor = explode('/', $composerData['name'])[0] ?? 'app'; // => "mycompany"

        return $vendor;
    }
    
    public static function resolve(array &$providers): array
    {
        $vendor = static::getAppVendor();

        foreach ($providers as $name => &$config) {
            $versionNumber = $config['version']['number'];
            $versionPrefix = $config['version']['prefix'];
            $fullVersion   = "{$versionPrefix}{$versionNumber}";
            $config['version']['pattern'] = preg_replace("/{vendor}/", $vendor, $config['version']['pattern']);
            $config['version']['pattern'] = preg_replace("/{version}/", $fullVersion, $config['version']['pattern']);
        }

        return $providers;
    }
}