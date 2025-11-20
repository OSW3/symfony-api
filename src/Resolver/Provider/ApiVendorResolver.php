<?php 
namespace OSW3\Api\Resolver\Provider;

final class ApiVendorResolver
{
    // public
    public static function resolve(array &$providers): array
    {
        // Resolve vendor from composer.json
        // --

        // $composer = json_decode(file_get_contents($this->kernel->getProjectDir() . '/composer.json'), true);
        // $vendor = explode('/', $composer['name'])[0] ?? 'app'; // => "mycompany"
        // $app    = explode('/', $composer['name'])[1] ?? 'app'; // => "myapp"


        
        // Resolve vendor from service.yaml
        // --

        // parameters:
        //     app.vendor_name: myapp
        // $vendor = $this->params->get('app.vendor_name');
        // $format = str_replace(['{vendor}', '{version}'], [$vendor, 'v'.$version], $headerFormat);


        foreach ($providers as $name => &$config) {
            // $versionNumber = $config['version']['number'];
            // $versionPrefix = $config['version']['prefix'];
            // $fullVersion = "{$versionPrefix}{$versionNumber}";
            // $config['version']['pattern'] = preg_replace("/{vendor}/", $fullVersion, $config['version']['pattern']);
            // $config['version']['pattern'] = preg_replace("/{version}/", $fullVersion, $config['version']['pattern']);
            $config['vendor'] = "myapp";
        }

        return $providers;
    }
}