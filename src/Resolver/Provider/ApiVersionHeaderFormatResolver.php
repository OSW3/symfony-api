<?php 
namespace OSW3\Api\Resolver\Provider;

use Symfony\Component\Filesystem\Path;

final class ApiVersionHeaderFormatResolver
{
    public static function getAppVendor(): ?string
    {
        // $composerFile = Path::join($_SERVER['PWD'] ?? \getcwd(), 'composer.json');

        // $previousFilePath = null;
        // $continue = true;
        // $i = 1;

        // // for ($i=1; $i < 30; $i++)
        // while ($continue)
        // {
        //     $filePath = Path::join(\dirname(__DIR__, $i++), 'composer.json');
        //     $fileExists = file_exists($filePath);

        //     if ($filePath === $previousFilePath || $fileExists) {
        //         $continue = false;
        //     }

        //     $previousFilePath = $filePath;
        // }
        // dump($composerFile);
        // dump($filePath);
        // dump($continue);

        // dd('--');
        // $composerFile = Path::join($_SERVER['PWD'] ?? \getcwd(), 'composer.json');
        $composerFile = Path::join(\dirname(__DIR__, 3), 'composer.json');
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