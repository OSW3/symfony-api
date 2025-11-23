<?php 
namespace OSW3\Api\Trait;
trait EnumTrait 
{
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

    public static function names(): array 
    {
        return array_map(fn($case) => $case->name, self::cases());
    }
    
    public static function values(): array 
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function has(string $name): bool 
    {
        return in_array($name, self::names(), true);
    }
}