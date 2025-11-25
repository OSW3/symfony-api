<?php 
namespace OSW3\Api\Subscribers;

use OSW3\Api\Enum\Version\Headers;
use OSW3\Api\Helper\HeaderHelper;
use OSW3\Api\Service\HeadersService;
use OSW3\Api\Service\VersionService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class VersionSubscriber implements EventSubscriberInterface 
{
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

        foreach (Headers::values() as $directive) 
        {
            $normalizedDirective = HeaderHelper::toHeaderCase($directive);

            if (!isset($exposed[$normalizedDirective]) || $exposed[$normalizedDirective] === false) {
                continue;
            }

            $value = match ($directive) {
                Headers::API_VERSION->value             => $this->versionService->getLabel(),
                Headers::API_ALL_VERSIONS->value        => $this->versionService->getAllVersions(),
                Headers::API_SUPPORTED_VERSIONS->value  => $this->versionService->getSupportedVersions(),
                Headers::API_DEPRECATED_VERSIONS->value => $this->versionService->getDeprecatedVersions(),
            };
            $value = HeaderHelper::toHeaderValue($value);

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