<?php 
namespace OSW3\Api\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class ClientService 
{
    private readonly Request $request;

    public function __construct(
        private readonly RequestStack $requestStack,
    ){
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getIp(): string 
    {
        return $this->request->getClientIp();
    }

    /**
     * "active", "inactive", "unknown"
     */
    public function getVpnStatus(): string 
    {
        // TODO: getVpnStatus
        return "unknown";
    }

    public function getUserAgent(): string 
    {
        return $this->request->headers->get('User-Agent');
    }

    public function getDevice(): string 
    {
        // TODO: getDevice
        return "device";
    }
    public function isMobile(): bool 
    {
        // TODO: isMobile
        return false;
    }
    public function isTablet(): bool 
    {
        // TODO: isTablet
        return false;
    }
    public function isDesktop(): bool 
    {
        // TODO: isDesktop
        return false;
    }

    public function getBrowser(): string 
    {
        // TODO: getBrowser
        return "browser";
    }
    public function getBrowserVersion(): string 
    {
        // TODO: getBrowserVersion
        return "";
    }

    public function getOs(): string 
    {
        // TODO: getOs
        return "os";
    }
    public function getOsVersion(): string 
    {
        // TODO: getOsVersion
        return "os version";
    }

    /**
     * (Blink, WebKit, Gecko…).
     */
    public function getEngine(): string 
    {
        $ua = (string) $this->getUserAgent();

        if (stripos($ua, 'Gecko/') !== false && stripos($ua, 'Firefox/') !== false) {
            return 'Gecko';
        }

        if (stripos($ua, 'AppleWebKit/') !== false) {
            if (stripos($ua, 'Chrome/') !== false || stripos($ua, 'Edg/') !== false || stripos($ua, 'OPR/') !== false) {
                return 'Blink';
            }
            return 'WebKit';
        }

        if (stripos($ua, 'Trident/') !== false || stripos($ua, 'MSIE') !== false) {
            return 'Trident';
        }

        if (stripos($ua, 'Presto/') !== false) {
            return 'Presto';
        }

        return 'unknown';
    }

    public function getLanguages(): array 
    {
        return $this->request->getLanguages();
    }
    public function getLanguage(): string 
    {
        return $this->request->getPreferredLanguage();
    }








// getClientFingerprint() — hash léger basé sur UA + resolution + timezone + langue pour identifier un client sans cookie (optionnel).

}