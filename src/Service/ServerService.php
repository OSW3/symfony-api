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

    /**
     * Get the server name (e.g. example.com)
     * -> Used to expose the server name in the API response
     * 
     * @return string
     */
    public function getName(): string 
    {
        return $_SERVER['SERVER_NAME'];
    }

    /**
     * Get the server software (e.g. Apache, Nginx, PHP (in PHP/8.4.8 (Development Server)) ...)
     * -> Used to expose the server software in the API response   
     * 
     * @return string
     */
    public function getSoftware(): string
    {
        $name = $_SERVER['SERVER_SOFTWARE'] ?? '';
        if (preg_match('/^[a-zA-Z]+/', $name, $matches)) {
            return $matches[0];
        }
        return 'Unknown';
    }

    /**
     * Get the server software version (e.g. 2.4.46, 1.18.0, 8.4.8 ...)
     * -> Used to expose the server software version in the API response
     * 
     * @return string
     */
    public function getSoftwareVersion(): string
    {
        $name = $_SERVER['SERVER_SOFTWARE'] ?? '';
        preg_match('/[0-9]+(\.[0-9]+)*/', $name, $matches);
        return $matches[0] ?? '';
    }

    /**
     * Get the server software release (e.g. Development Server in PHP/8.4.8 (Development Server))
     * -> Used to expose the server software release in the API response
     * 
     * @return string
     */
    public function getSoftwareRelease(): string 
    {
        $name = $_SERVER['SERVER_SOFTWARE'] ?? '';
        preg_match('/\((.*?)\)/', $name, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Get the server OS (e.g. Darwin, Linux, Windows)
     * -> Used to expose the server OS in the API response
     * 
     * @return string
     */
    public function getOs(): string 
    {
        return php_uname('s');
    }

    /**
     * Get the server OS version (e.g. 20.3.0, 10.0.19042)
     * -> Used to expose the server OS version in the API response
     * 
     * @return string
     */
    public function getOsVersion(): string 
    {
        return php_uname('r');
    }

    /**
     * Get the server OS release (e.g. Darwin Kernel Version 20.3.0, Windows NT 10.0.19042)
     * -> Used to expose the server OS release in the API response
     * 
     * @return string
     */
    public function getOsRelease(): string 
    {
        return php_uname('v');
    }
    
    /**
     * Get the server date (Y-m-d)
     * -> Used to expose the date in the API response
     * 
     * @return string
     */
    public function getDate(): string
    {
        $timezone = new \DateTimeZone($this->getTimezone());
        $now = new \DateTime('now', $timezone);
        return $now->format('Y-m-d');
    }

    /**
     * Get the current server time
     * -> Used to expose the time in the API response
     * 
     * @return string
     */
    public function getTime(): string
    {
        $timezone = new \DateTimeZone($this->getTimezone());
        $now = new \DateTime('now', $timezone);
        return $now->format('H:i:s');
    }

    /**
     * Get the server timezone
     * -> Used to expose the timezone in the API response
     * 
     * @return string
     */
    public function getTimezone(): string
    {
        return date_default_timezone_get() ?: 'UTC';
    }

    /**
     * Get the server region
     * -> Used to expose the region in the API response
     *
     * @return string|null
     */
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