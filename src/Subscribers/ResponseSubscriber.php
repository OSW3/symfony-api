<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Encoder\ToonEncoder;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\HeadersService;
use OSW3\Api\Service\ResponseService;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ContextService $contextService,
        private readonly ResponseService $responseService,
        private readonly HeadersService $headersService,
        private readonly ConfigurationService $configurationService,
        private readonly ToonEncoder $toonEncoder,
    ){}
    
    public static function getSubscribedEvents(): array
    {
        return [
            // KernelEvents::RESPONSE => ['onResponse', -10],
            KernelEvents::RESPONSE => ['onResponse', 0],
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Get current response
        $response = $event->getResponse();


        // $format = $this->responseService->getFormat();

        // if ($format === 'toon') {
        //     $content = $this->toonEncoder->encode($response->getContent());
        //     $response->setContent($content);
        //     $response->headers->set('Content-Type', 'application/toon');
        // }
        // dd($format);
        // dd($response);

        // dd($response->headers->all());
    }
}