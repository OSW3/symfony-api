<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\HeadersService;
use OSW3\Api\Service\IntegrityService;
use OSW3\Api\Service\CacheControlService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles setting Cache-Control headers on API responses.
 * Used to control caching behavior for clients and intermediaries.
 */
final class CacheControlSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly HeadersService $headersService,
        private readonly IntegrityService $integrityService,
        private readonly CacheControlService $cacheControlService,
    ){}
    
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', 0],
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Get current response
        $response  = $event->getResponse();
        $exposed   = $this->headersService->getExposedDirectives();

        if ($this->cacheControlService->isEnabled()) {
            $response->headers->set(
                'Cache-Control', 
                $this->cacheControlService->toString()
            );
        }
        
        if (isset($exposed['ETag']) && $exposed['ETag'] === true) {
            $response->headers->set('ETag', sprintf(
                "W/\"%s\"",
                $this->integrityService->getHash('md5')
            ));
        }
    }
}