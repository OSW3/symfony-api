<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\HeadersService;
use OSW3\Api\Service\ResponseService;
use OSW3\Api\Service\IntegrityService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class IntegritySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly HeadersService $headersService,
        private readonly IntegrityService $integrityService,
        private readonly ResponseService $responseService,
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

        $isEnabled = $this->integrityService->isEnabled();
        $algorithm = $this->integrityService->getAlgorithm();
        $hash      = $this->integrityService->getHash($algorithm);
        $base64    = base64_encode(hex2bin($hash) ?: '');

        if ($isEnabled) {
            $response->headers->set('Digest', "{$algorithm}={$base64}");
        }
    }
}