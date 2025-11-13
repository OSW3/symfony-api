<?php 
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\HeadersService;
use OSW3\Api\Service\VersionService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class VersionSubscriber implements EventSubscriberInterface 
{
    private const ALLOWED_DIRECTIVES = [
        'API-Version',
        'X-API-Version',
        'X-API-All-Versions',
        'X-API-Supported-Versions',
        'X-API-Deprecated-Versions',
    ];

    public function __construct(
        private readonly HeadersService $headersService,
        private readonly VersionService $versionService,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', 0]
        ];
    }
    
    public function onResponse(ResponseEvent $event): void 
    {
        // Is main request
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $exposed = $this->headersService->getExposedDirectives();


        foreach (self::ALLOWED_DIRECTIVES as $directive) 
        {
            $normalizedDirective = $this->headersService->toHeaderCase($directive);

            if (!isset($exposed[$normalizedDirective]) || $exposed[$normalizedDirective] === false) {
                continue;
            }

            $value = match ($directive) {
                'API-Version'               => $this->versionService->getLabel(),
                'X-API-Version'             => $this->versionService->getLabel(),
                'X-API-All-Versions'        => $this->versionService->getAllVersions(),
                'X-API-Supported-Versions'  => $this->versionService->getSupportedVersions(),
                'X-API-Deprecated-Versions' => $this->versionService->getDeprecatedVersions(),
            };
            $value = $this->headersService->toHeaderValue($value);

            if (empty($value)) {
                continue;
            }

            $response->headers->set($directive, $value);
        }

        if ($this->versionService->getLocation() === 'header') 
        {
            $response->headers->set(
                $this->versionService->getHeaderDirective(),
                $this->versionService->getHeaderPattern(),
            );
        }

    }
}