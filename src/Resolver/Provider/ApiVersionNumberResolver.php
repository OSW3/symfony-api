<?php 
namespace OSW3\Api\Resolver\Provider;

final class ApiVersionNumberResolver
{
    public static function resolve(array &$providers): array
    {
        $usedNumbers = [];

        // Step 1: Collect all previously defined versions
        foreach ($providers as $name => $config) {
            $number = $config['version']['number'] ?? null;

            if (!empty($number)) {
                $usedNumbers[] = (int) $number;
            }
        }

        // Step 2: Automatically assign the missing ones with the first available number
        foreach ($providers as $name => &$config) {
            $number = $config['version']['number'] ?? null;

            if ($number === null || $number === '') {
                $n = 1;
                while (in_array($n, $usedNumbers, true)) {
                    $n++;
                }

                $config['version']['number'] = $n;
                $usedNumbers[] = $n;
            }
        }

        return $providers;
    }
}