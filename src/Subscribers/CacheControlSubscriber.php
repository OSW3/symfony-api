<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\HeadersService;
use OSW3\Api\Service\ChecksumService;
use OSW3\Api\Service\ResponseService;
use OSW3\Api\Service\CacheControlService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CacheControlSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly HeadersService $headersService,
        private readonly ChecksumService $checksumService,
        private readonly ResponseService $responseService,
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
            $data = $this->responseService->getData();
            $data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
            $md5  = $this->checksumService->computeHash($data, 'md5');
            $response->headers->set(
                'ETag', 
                "W/\"{$md5}\""
            );
        }
    }
}