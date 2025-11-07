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
        return $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
    }
    
    public function getSoftwareName(): string
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

    /**
     * Get the server uptime
     * 
     * @return string
     */
    public function getUptime(): string
    {
        $uptime = shell_exec("uptime");
        return $uptime ?: 'Unknown';
    }

    /**
     * Get the server load average
     * 
     * @return string
     */
    public function getLoadAverage(): string
    {
        $load = sys_getloadavg();
        return implode(', ', $load);
    }

    /**
     * Get the server memory limit
     * 
     * @return string|null
     */
    public function getMemoryLimit(): ?string
    {
        return ini_get('memory_limit');
    }

    /** Get the server total memory
     * 
     * @return string|null
     */
    public function getTotalMemory(): ?string
    {
        return shell_exec("free -m | awk 'NR==2{printf \"%s\", $2}'");
    }

    /**
     * Get the server free memory
     * 
     * @return string|null
     */
    public function getFreeMemory(): ?string
    {
        return shell_exec("free -m | awk 'NR==2{printf \"%s\", $4}'");
    }

    /** Get the server available memory
     * 
     * @return string|null
     */
    public function getAvailableMemory(): ?string
    {
        return shell_exec("free -m | awk 'NR==2{printf \"%s\", $7}'");
    }

    /** Get the server used memory
     * 
     * @return string|null
     */
    public function getUsedMemory(): ?string
    {
        return shell_exec("free -m | awk 'NR==2{printf \"%s\", $3}'");
    }

    /** Get the server memory usage in percentage
     * 
     * @return string
     */
    public function getMemoryUsage(): string
    {
        $total = $this->getTotalMemory();
        $used = $this->getUsedMemory();
        return $total && is_numeric($used) ? round(($used / (int)$total) * 100, 2) . '%' : 'Unknown';
    }

    /** Get the server total disk space
     * 
     * @return string
     */
    public function getTotalDisk(): string
    {
        return shell_exec("df -h | awk 'NR==2{printf \"%s\", $2}'");
    }

    /** Get the server free disk space
     * 
     * @return string
     */
    public function getFreeDisk(): string
    {
        return shell_exec("df -h | awk 'NR==2{printf \"%s\", $4}'");
    }

    /** Get the server used disk space
     * 
     * @return string
     */
    public function getUsedDisk(): string
    {
        return shell_exec("df -h | awk 'NR==2{printf \"%s\", $3}'");
    }

    /** Get the server disk usage in percentage
     * 
     * @return string
     */
    public function getDiskUsage(): string
    {
        $total = $this->getTotalDisk();
        $used = $this->getUsedDisk();
        return $total && is_numeric($used) ? round(($used / (int)$total) * 100, 2) . '%' : 'Unknown';
    }

    /** Get the database driver
     * 
     * @return string
     */
    public function getDatabaseDriver(): string
    {
        return $_ENV['DATABASE_DRIVER'] ?? 'Unknown';
    }

    /** Get the database version
     * 
     * @return string
     */
    public function getDatabaseVersion(): string
    {
        return $_ENV['DATABASE_VERSION'] ?? 'Unknown';
    }

    /** Get the CPU manufacturer
     * 
     * @return string|null
     */
    public function getCpuManufacturer(): ?string
    {
        return shell_exec("cat /proc/cpuinfo | grep 'model name' | uniq | awk -F: '{print $2}'");
    }

    /** Get the CPU model
     * 
     * @return string|null
     */
    public function getCpuModel(): ?string
    {
        return shell_exec("cat /proc/cpuinfo | grep 'model name' | uniq | awk -F: '{print $2}'");
    }

    /** Get the number of CPU cores
     * 
     * @return int
     */
    public function getCpuCores(): int
    {
        return (int)shell_exec("nproc");
    }

    /** Get the CPU speed
     * 
     * @return string|null
     */
    public function getCpuSpeed(): ?string
    {
        return shell_exec("cat /proc/cpuinfo | grep 'cpu MHz' | uniq | awk -F: '{print $2}'");
    }

    /** Get the CPU threads
     * 
     * @return int
     */
    public function getCpuThreads(): int
    {
        return (int)shell_exec("cat /proc/cpuinfo | grep 'cpu cores' | uniq | awk -F: '{print $2}'");
    }

    /** Get the CPU physical cores
     * 
     * @return int
     */
    public function getCpuPhysicalCores(): int
    {
        return (int)shell_exec("cat /proc/cpuinfo | grep 'cpu cores' | uniq | awk -F: '{print $2}'");
    }

    /** Get the CPU logical cores
     * 
     * @return int
     */
    public function getCpuLogicalCores(): int
    {
        return (int)shell_exec("cat /proc/cpuinfo | grep 'cpu cores' | uniq | awk -F: '{print $2}'");
    }

    /** Get the CPU cache size
     * 
     * @return string|null
     */
    public function getCpuCache(): ?string
    {
        return shell_exec("cat /proc/cpuinfo | grep 'cache size' | uniq | awk -F: '{print $2}'");
    }

    /**
     * Get the architecture of the server (e.g. x86_64, arm64 ...)
     * 
     * @return string
     */
    public function getArchitecture(): string
    {
        return shell_exec("uname -m");
    }

    /**
     * Get the kernel version
     * 
     * @return string
     */
    public function getKernelVersion(): string
    {
        return shell_exec("uname -r");
    }

    /**
     * Get the document root of the server
     * 
     * @return string
     */
    public function getDocumentRoot(): string
    {
        return $_SERVER['DOCUMENT_ROOT'] ?? '';
    }
}