<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\IntegrityService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * IntegritySubscriber
 * 
 * Adds integrity checksum headers to API responses if enabled.
 */
final class IntegritySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly IntegrityService $integrityService,
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

        if (! $this->integrityService->isEnabled()) {
            return;
        }

        // Get current response
        $response  = $event->getResponse();

        $algorithm = $this->integrityService->getAlgorithm();
        $hash      = $this->integrityService->getHash($algorithm);
        $base64    = base64_encode(hex2bin($hash) ?: '');

        $response->headers->set('Digest', "{$algorithm}={$base64}");
    }
}