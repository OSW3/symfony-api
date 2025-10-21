<?php 
namespace OSW3\Api\Service;

use UAParser\Parser;
use Detection\MobileDetect;
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

    
    // ──────────────────────────────
    // IP
    // ──────────────────────────────

    public function getIp(): string 
    {
        return $this->request->getClientIp();
    }

    
    // ──────────────────────────────
    // User Agent
    // ──────────────────────────────

    public function getUserAgent(?string $fragment=null): ?string 
    {
        $ua = (string) $this->request->headers->get('User-Agent');

        $parser = Parser::create();
        $result = $parser->parse($ua);

        return match ($fragment) {
            'browser_family'  => $result->ua->family,
            'browser_major'   => $result->ua->major,
            'browser_minor'   => $result->ua->minor,
            'browser_patch'   => $result->ua->patch,
            'browser_version' => $result->ua->toVersion(),
            'os_family'       => $result->os->family,
            'os_major'        => $result->os->major,
            'os_minor'        => $result->os->minor,
            'os_patch'        => $result->os->patch,
            'os_version'      => $result->os->toVersion(),
            default     => $ua,
        };
    }

    
    // ──────────────────────────────
    // Device
    // ──────────────────────────────

    public function getDevice(): string 
    {
        return match (true) {
            $this->isMobile() => 'mobile',
            $this->isTablet() => 'tablet',
            default           => 'desktop',
        };
    }
    public function isMobile(): bool 
    {
        return (new MobileDetect())->isMobile();
    }
    public function isTablet(): bool 
    {
        return (new MobileDetect())->isTablet();
    }
    public function isDesktop(): bool 
    {
        return !$this->isMobile() && !$this->isTablet();
    }

    
    // ──────────────────────────────
    // Browser
    // ──────────────────────────────

    public function getBrowser(): ?string
    {
        return $this->getUserAgent('browser_family');
    }
    public function getBrowserVersion(): ?string
    {
        return $this->getUserAgent('browser_version');
    }
    public function getBrowserVersionMajor(): ?string
    {
        return $this->getUserAgent('browser_major');
    }
    public function getBrowserVersionMinor(): ?string
    {
        return $this->getUserAgent('browser_minor');
    }
    public function getBrowserVersionPatch(): ?string
    {
        return $this->getUserAgent('browser_patch');
    }

    
    // ──────────────────────────────
    // OS
    // ──────────────────────────────

    public function getOs(): ?string
    {
        return $this->getUserAgent('os_family');
    }
    public function getOsVersion(): ?string
    {
        return $this->getUserAgent('os_version');
    }
    public function getOsVersionMajor(): ?string
    {
        return $this->getUserAgent('os_major');
    }
    public function getOsVersionMinor(): ?string
    {
        return $this->getUserAgent('os_minor');
    }
    public function getOsVersionPatch(): ?string
    {
        return $this->getUserAgent('os_patch');
    }

    
    // ──────────────────────────────
    // Engine
    // ──────────────────────────────

    /**
     * (Blink, WebKit, Gecko…).
     */
    public function getEngine(): ?string
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

    
    // ──────────────────────────────
    // Languages
    // ──────────────────────────────

    public function getLanguages(): array 
    {
        return $this->request->getLanguages();
    }
    public function getLanguage(): ?string
    {
        return $this->request->getPreferredLanguage();
    }

    
    // ──────────────────────────────
    // Fingerprint
    // ──────────────────────────────

    public function getFingerprint(): ?string
    {
        $data = [
            $this->getUserAgent(),
            implode(',', $this->getLanguages()),
            $this->request->headers->get('Sec-CH-UA-Platform', ''),
            $this->request->headers->get('Sec-CH-UA-Mobile', ''),
            $this->request->headers->get('DNT', ''),
        ];

        return hash('sha256', implode('|', $data));
    }
}