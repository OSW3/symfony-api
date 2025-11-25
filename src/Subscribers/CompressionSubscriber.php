<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\CompressionService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CompressionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CompressionService $compressionService,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', -11]
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->compressionService->isEnabled()) {
            return;
        }

        // Get current response
        $response = $event->getResponse();

        // Get current content
        $content = $response->getContent();
        
        // Get compression settings
        $format = $this->compressionService->getFormat();
        $level  = $this->compressionService->getLevel();

        // 
        switch ($format) {
            case 'gzip':
                $content = gzencode($content, $level);
                break;

            case 'deflate':
                $content = gzdeflate($content, $level);
                break;

            case 'brotli':
                if (!function_exists('brotli_compress')) {
                    return;
                }

                $content = \brotli_compress($content, $level);
                break;
        }

        // Set the compressed content and headers
        $response->setContent($content);
        $response->headers->set('Content-Encoding', $format);
        $response->headers->set('Vary', 'Accept-Encoding');
    }
}