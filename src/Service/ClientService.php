<?php 
namespace OSW3\Api\Service;

use UAParser\Parser;
use Detection\MobileDetect;
use OSW3\Api\Enum\Client\BrowserEngine;
use OSW3\Api\Enum\Client\Device;
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

    public function getIps(): array 
    {
        return $this->request->getClientIps();
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
            default           => $ua,
        };
    }

    
    // ──────────────────────────────
    // Device
    // ──────────────────────────────

    public function getDevice(): string 
    {
        return match (true) {
            $this->isMobile() => Device::MOBILE->value,
            $this->isTablet() => Device::TABLET->value,
            default           => Device::DESKTOP->value,
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

    public function getBrowser(): string
    {
        return $this->getUserAgent('browser_family') ?? 'unknown';
    }
    public function getBrowserVersion(): string
    {
        return $this->getUserAgent('browser_version') ?? '';
    }
    public function getBrowserVersionMajor(): string
    {
        return $this->getUserAgent('browser_major') ?? '';
    }
    public function getBrowserVersionMinor(): string
    {
        return $this->getUserAgent('browser_minor') ?? '';
    }
    public function getBrowserVersionPatch(): string
    {
        return $this->getUserAgent('browser_patch') ?? '';
    }

    
    // ──────────────────────────────
    // OS
    // ──────────────────────────────

    public function getOs(): string
    {
        return $this->getUserAgent('os_family') ?? 'unknown';
    }
    public function getOsVersion(): string
    {
        return $this->getUserAgent('os_version') ?? '';
    }
    public function getOsVersionMajor(): string
    {
        return $this->getUserAgent('os_major') ?? '';
    }
    public function getOsVersionMinor(): string
    {
        return $this->getUserAgent('os_minor') ?? '';
    }
    public function getOsVersionPatch(): string
    {
        return $this->getUserAgent('os_patch') ?? '';
    }

    
    // ──────────────────────────────
    // Engine
    // ──────────────────────────────

    /**
     * Get the browser engine (Blink, WebKit, Gecko…).
     * 
     * @return string|null
     */
    public function getEngine(): ?string
    {
        $ua = (string) $this->getUserAgent();

        if (stripos($ua, 'Gecko/') !== false && stripos($ua, 'Firefox/') !== false) {
            return BrowserEngine::GECKO->value;
        }

        if (stripos($ua, 'AppleWebKit/') !== false) {
            if (stripos($ua, 'Chrome/') !== false || stripos($ua, 'Edg/') !== false || stripos($ua, 'OPR/') !== false) {
                return BrowserEngine::BLINK->value;
            }
            return BrowserEngine::WEBKIT->value;
        }

        if (stripos($ua, 'Trident/') !== false || stripos($ua, 'MSIE') !== false) {
            return BrowserEngine::TRIDENT->value;
        }

        if (stripos($ua, 'Presto/') !== false) {
            return BrowserEngine::PRESTO->value;
        }

        return BrowserEngine::UNKNOWN->value;
    }

    
    // ──────────────────────────────
    // Languages
    // ──────────────────────────────

    /**
     * Get all accepted languages
     * 
     * @return array
     */
    public function getLanguages(): array 
    {
        return $this->request->getLanguages();
    }

    /**
     * Get the preferred language
     * 
     * @return string|null
     */
    public function getLanguage(): ?string
    {
        return $this->request->getPreferredLanguage();
    }

    
    // ──────────────────────────────
    // Charsets
    // ──────────────────────────────

    /**
     * Get all accepted charsets
     * 
     * @return array
     */
    public function getCharsets(): array
    {
        return $this->request->getCharsets();
    }

    /**
     * Get all accepted content types
     * 
     * @return array
     */
    public function getAcceptableContentTypes(): array
    {
        return $this->request->getAcceptableContentTypes();
    }

    /**
     * Get all accepted encodings
     * 
     * @return array
     */
    public function getEncodings(): array
    {
        return $this->request->getEncodings();
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