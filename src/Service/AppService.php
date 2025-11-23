<?php 
namespace OSW3\Api\Service;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\KernelInterface;

final class AppService 
{
    private ?array $dataCache = null;

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
        $appName = $this->getComposerData()['name'] ?? null;

        if (empty($appName)) {
            return 'app';
        }

        return explode('/', $appName)[1] ?? 'app';
    }

    /**
     * Get the ve,dor of the App from composer.json
     * 
     * @return string
     */
    public function getVendor(): string 
    {
        $appName = $this->getComposerData()['name'] ?? null;

        if (empty($appName)) {
            return 'vendor';
        }
        
        return explode('/', $appName)[0] ?? 'vendor';
    }

    /**
     * Get the version of the App from composer.json
     * 
     * @return string
     */
    public function getVersion(): string 
    {
        return $this->getComposerData()['version'] ?? '0.0.0';
    }

    /**
     * Get the description of the App from composer.json
     * 
     * @return string
     */
    public function getDescription(): string
    {
        return $this->getComposerData()['description'] ?? '';
    }

    /**
     * Get the license of the App from composer.json
     * 
     * @return string
     */
    public function getLicense(): string
    {
        return $this->getComposerData()['license'] ?? '';
    }

    private function getComposerData(): array
    {
        if ($this->dataCache === null) {
            try {
                $file = Path::join($this->kernel->getProjectDir(), 'composer.json');
                $data = file_get_contents($file);
                $this->dataCache = json_decode($data, true);
            } catch (\Throwable $e) {
                $this->dataCache = [];
            }
        }

        return $this->dataCache;
    }
}