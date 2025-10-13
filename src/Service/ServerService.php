<?php 
namespace OSW3\Api\Service;

final class ServerService 
{
    public function getIp(): string 
    {
        if (!empty($_SERVER['SERVER_ADDR'])) {
            return $_SERVER['SERVER_ADDR'];
        }

        if (php_sapi_name() !== 'cli') {
            $host = gethostname();
            if ($host !== false) {
                $ip = gethostbyname($host);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '127.0.0.1';
    }

    public function getHostname(): string 
    {
        return gethostname();
    }

    public function getEnvironment(): string 
    {
        return $_ENV['APP_ENV'] ?? 'prod';
    }

    public function getPhpVersion(): string 
    {
        return PHP_VERSION;
    }

    public function getSymfonyVersion(): string 
    {
        return \Symfony\Component\HttpKernel\Kernel::VERSION;
    }

    public function getName(): string 
    {
        return $_SERVER['SERVER_NAME'];
    }

    public function getOs(): string 
    {
        return php_uname('s');
    }
    public function getOsVersion(): string 
    {
        return php_uname('r');
    }
    public function getOsRelease(): string 
    {
        return php_uname('v');
    }
    
    public function getDate(): string
    {
        $timezone = new \DateTimeZone($this->getTimezone());
        $now = new \DateTime('now', $timezone);
        return $now->format('Y-m-d');
    }
    public function getTime(): string
    {
        $timezone = new \DateTimeZone($this->getTimezone());
        $now = new \DateTime('now', $timezone);
        return $now->format('H:i:s');
    }
    public function getTimezone(): string
    {
        return date_default_timezone_get() ?: 'UTC';
    }
    
    public function getRegion(): ?string
    {
        $ip = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
    
        $response = @file_get_contents("https://ipapi.co/{$ip}/json/");
        if ($response === false) {
            return null;
        }
    
        $data = json_decode($response, true);
        return $data['region'] ?? null;


        // composer require geoip2/geoip2
        // $reader = new Reader('/usr/share/GeoIP/GeoLite2-City.mmdb');
        // $record = $reader->city($_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname()));
        // return $record->subdivisions[0]->name ?? null;
    }
}