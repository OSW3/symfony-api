<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Enum\MimeType;
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

        // Get current content
        $content  = $response->getContent();

        // Get the output format
        $format   = $this->responseService->getFormat();

        // Get the output mime type
        $mimeType = $this->responseService->getMimeType();

        // Set the Header Content-Type
        $response->headers->set('Content-Type', $mimeType);

        // Set the formatted content
        $response->setContent(match ($mimeType) {
            MimeType::XML->value  => $this->responseService->getXmlResponse($content),
            MimeType::YAML->value => $this->responseService->getYamlResponse($content),
            MimeType::CSV->value  => $this->responseService->getCsvResponse($content),
            MimeType::TOON->value => $this->responseService->getToonResponse($content),
            default               => $content,
        });
        

        // dd($response->headers->all());
    }
}