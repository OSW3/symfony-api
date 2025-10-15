<?php 
namespace OSW3\Api\Service;

final class ServerService 
{
    /** Get the server IP address
     * 
     * @return string
     */
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

    /** Get the server hostname
     * 
     * @return string
     */
    public function getHostname(): string 
    {
        return gethostname();
    }

    /** Get the application environment (dev, prod, test)
     * 
     * @return string
     */
    public function getEnvironment(): string 
    {
        return $_ENV['APP_ENV'] ?? 'prod';
    }

    /** Get the PHP version
     * 
     * @return string
     */
    public function getPhpVersion(): string 
    {
        return PHP_VERSION;
    }

    /** Get the Symfony version
     * 
     * @return string
     */
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
     * Get the server software name (e.g. Apache, nginx, IIS ...)
     * -> Used to expose the server software name in the API response
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
     * Get the server software version (e.g. Apache/2.4.41, nginx/1.18.0 ...)
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
     * Get the server software release (e.g. Apache/2.4.41 (Ubuntu), nginx/1.18.0 (Ubuntu) ...)
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
     * Get the server OS (e.g. Linux, Windows, Darwin ...)
     * -> Used to expose the server OS in the API response
     * 
     * @return string
     */
    public function getOs(): string 
    {
        return php_uname('s');
    }

    /**
     * Get the server OS version (e.g. 5.15.0-1051-azure, 10.0.19042 ...)
     * -> Used to expose the server OS version in the API response
     * 
     * @return string
     */
    public function getOsVersion(): string 
    {
        return php_uname('r');
    }

    /**
     * Get the server OS release (e.g. 10.15.7, 20.04, 21H1 ...)
     * -> Used to expose the server OS release in the API response
     * 
     * @return string
     */
    public function getOsRelease(): string 
    {
        return php_uname('v');
    }

    /**
     * Get the server date in YYYY-MM-DD format
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
     * Get the server time
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
     * 
     * @return string
     */
    public function getTimezone(): string
    {
        return date_default_timezone_get() ?: 'UTC';
    }

    /**
     * Get the server region based on its IP address
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