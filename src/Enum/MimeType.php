<?php 
namespace OSW3\Api\Enum;

enum MimeType: string 
{
    case JSON = 'application/json';
    case XML  = 'application/xml';
    case YAML = 'application/x-yaml';
    case CSV  = 'text/csv';
    case TOON = 'application/x-toon';
    case TOML = 'application/toml';


    public static function fromFormat(string $format): self
    {
        $format = strtolower($format);

        $map = array_combine(
            array_map(fn($c) => strtolower($c->name), self::cases()),
            self::cases()
        );

        if (!isset($map[$format])) {
            throw new \InvalidArgumentException("Unknown format '$format'");
        }

        return $map[$format];
    }


    public static function fromDefault(string $case, string $default = 'default')
    {
        if ($try = self::tryFrom($case))
        {
            return $try;
        }
        return self::tryFrom($default);
    }

    public static function toString(string $separator=',', string $start='', string $end=''): string
    {
        $cases = self::cases();
        $map = array_map(fn($case) => $case->name, $cases);
        return $start . implode($separator, $map) . $end;
    }

    public static function toArray(bool $lowerCase = false): array 
    {
        $output = [];
        $cases = self::cases();

        array_walk($cases, function($case) use (&$output, $lowerCase) {
            $output[$lowerCase ? strtolower($case->name) : $case->name] = $case->value;
        });

        return $output;
    }
} 