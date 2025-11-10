<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\HeadersService;
use OSW3\Api\Service\ChecksumService;
use OSW3\Api\Service\ResponseService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ChecksumSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly HeadersService $headersService,
        private readonly ChecksumService $checksumService,
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

        $isEnabled = $this->checksumService->isEnabled();
        $algorithm = $this->checksumService->getAlgorithm();
        $data      = $this->responseService->getData();
        $data      = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        
        if ($isEnabled) {
            $hash       = $this->checksumService->computeHash($data, $algorithm);
            $base64Hash = base64_encode(hex2bin($hash) ?: '');
            
            $response->headers->set(
                'Digest', 
                "{$algorithm}={$base64Hash}"
            );
        }
    }
}