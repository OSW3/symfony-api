<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\TemplateService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TemplateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TemplateService $templateService,
    ){}
    
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', -9],
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
        $content = $response->getContent();

        // Get the template type
        $type = $this->templateService->getType();

        // Render the template content
        $content = $this->templateService->render($response, $type, false);

        // Set the formatted content
        $response->setContent($content);
    }
}