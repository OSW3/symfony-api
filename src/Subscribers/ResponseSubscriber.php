<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Enum\MimeType;
use OSW3\Api\Service\ResponseService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ResponseService $responseService,
    ){}
    
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', -10],
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Get current response
        $response = $event->getResponse();

        // Get the output format
        $format   = $this->responseService->getFormat();

        // Get the output mime type
        $mimeType = $this->responseService->getMimeType();

        // Get current content
        $content = match ($mimeType) {
            MimeType::CSV->value  => $this->responseService->getCsvResponse($response->getContent()),
            MimeType::XML->value  => $this->responseService->getXmlResponse($response->getContent()),
            MimeType::YAML->value => $this->responseService->getYamlResponse($response->getContent()),
            MimeType::TOON->value => $this->responseService->getToonResponse($response->getContent()),
            default               => $response->getContent(),
        };

        // Set the Header Content-Type
        $response->headers->set('Content-Type', $mimeType);

        // Set the formatted content
        $response->setContent($content);
    }
}