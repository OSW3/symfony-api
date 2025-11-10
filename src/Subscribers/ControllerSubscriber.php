<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\ResponseService;
use OSW3\Api\Service\IntegrityService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ControllerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ResponseService $responseService,
        private readonly IntegrityService $integrityService,
    ){}
    
    public static function getSubscribedEvents(): array
    {
        return [
            // KernelEvents::CONTROLLER => ['onBeforeController', 0],
            KernelEvents::RESPONSE => ['onAfterController', 100],
        ];
    }

    public function onBeforeController($event): void
    {
        // dump('CONTROLLER - ControllerSubscriber::onController');
    }

    public function onAfterController(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Get current response
        $response = $event->getResponse();

        // Get current content
        $content = $response->getContent();

        // Get the template data
        $data = json_decode($content, true);

        // Get the data count
        $count = is_array($data) ? count($data) : 0;

        // Get the content size
        $size = strlen((string) $content);

        // Get the integrity algorithm
        $algorithm = $this->integrityService->getAlgorithm();

        // Set the response size and count
        $this->responseService->setSize($size);
        $this->responseService->setCount($count);

        // Compute the data hash
        $this->integrityService->computeHash($content, 'md5');
        $this->integrityService->computeHash($content, 'sha1');
        $this->integrityService->computeHash($content, 'sha256');
        $this->integrityService->computeHash($content, 'sha512');
    }
}