<?php 
namespace OSW3\Api\Resolver;

final class ApiResolver
{
    public static function execute(array &$config): array
    {
        $usedNumbers  = [];
        $vendor       = static::getAppVendor();
        $vendor       = $vendor ?: 'app';

        // Collect all previously defined versions
        foreach ($config['providers'] as $name => $provider) {
            $number = $provider['version']['number'] ?? null;

            if (!empty($number)) {
                $usedNumbers[] = (int) $number;
            }
        }

        // Step 2: Automatically assign the missing ones with the first available number
        foreach ($config['providers'] as $name => &$provider) {
            if (
                empty($provider['version']) ||
                !is_array($provider['version'])
            ) {
                $provider['version'] = [
                    'number'  => null,
                    'prefix'  => '',
                    'pattern' => '',
                ];
            }

            // Hash the version info
            $number = $provider['version']['number'] ?? null;

            // Auto assign the version number if not defined
            if ($number === null || $number === '') {
                $new = 1;
                while (in_array($new, $usedNumbers, true)) {
                    $new++;
                }
                $provider['version']['number'] = $new;
                $usedNumbers[] = $new;
                $number = $new;
            }

            // Pattern
            $prefix       = $provider['version']['prefix']  ?? '';
            $pattern      = $provider['version']['pattern'] ?? '';
            $fullVersion  = $prefix . $number;

            // Replacements
            if (is_string($pattern) && $pattern !== '') {
                $search  = ['/{vendor}/', '/{version}/'];
                $replace = [$vendor, $fullVersion];
                $provider['version']['pattern'] = preg_replace($search, $replace, $pattern);
            }
        }

        return $config;
    }

    private static function getAppVendor(): ?string
    {
        $startDir = $_SERVER['PWD'] ?? getcwd();
        $composerFile = self::findComposerJson($startDir);

        $vendor = null;

        if ($composerFile !== null) {
            $json = @file_get_contents($composerFile);
            $data = json_decode($json, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $name = $data['name'] ?? '';
                $vendor = explode('/', $name)[0] ?? null;
            }
        }

        $fallbacks = [
            $_SERVER['SERVER_NAME'] ?? null,
            $_SERVER['HTTP_HOST'] ?? null,
            $_SERVER['SERVER_ADDR'] ?? null,
            gethostname(),
        ];

        foreach ($fallbacks as $fb) {
            if (!empty($fb)) {
                return $vendor ?: $fb;
            }
        }

        return $vendor;
    }

    private static function findComposerJson(string $dir): ?string
    {
        $previous = null;
        $level = 1;

        while (true) {
            $current = dirname($dir, $level++);
            if ($current === $previous) {
                return null;
            }

            $file = $current . DIRECTORY_SEPARATOR . 'composer.json';
            if (is_file($file)) {
                return realpath($file);
            }

            $previous = $current;
        }
    }
}