<?php 
namespace OSW3\Api\Service;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\KernelInterface;

final class AppService 
{
    public function __construct(
        private readonly KernelInterface $kernel,
    ){}

    /**
     * Get the name of the App from composer.json
     * 
     * @return string
     */
    public function getName(): string 
    {
        $composerFile = Path::join($this->kernel->getProjectDir(), '/composer.json');
        $composerJson = file_get_contents($composerFile);
        $composerData = json_decode($composerJson, true);

        return explode('/', $composerData['name'])[1] ?? 'app';
    }

    /**
     * Get the ve,dor of the App from composer.json
     * 
     * @return string
     */
    public function getVendor(): string 
    {
        $composerFile = Path::join($this->kernel->getProjectDir(), '/composer.json');
        $composerJson = file_get_contents($composerFile);
        $composerData = json_decode($composerJson, true);

        return explode('/', $composerData['name'])[0] ?? 'vendor';
    }

    /**
     * Get the version of the App from composer.json
     * 
     * @return string
     */
    public function getVersion(): string 
    {
        $composerFile = Path::join($this->kernel->getProjectDir(), '/composer.json');
        $composerJson = file_get_contents($composerFile);
        $composerData = json_decode($composerJson, true);

        return $composerData['version'] ?? '0.0.0';
    }
}