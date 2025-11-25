<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Enum\Hash\Algorithm;
use OSW3\Api\Service\ResponseService;
use OSW3\Api\Service\IntegrityService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber to handle actions after controller execution.
 * Used to set response size, count, and compute data integrity hashes.
 */
final class ControllerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ResponseService $responseService,
        private readonly IntegrityService $integrityService,
    ){}
    
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onAfterController', 100],
        ];
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
        // $algorithm = $this->integrityService->getAlgorithm();

        // Set the response size and count
        $this->responseService->setSize($size);
        $this->responseService->setCount($count);

        // Compute the data hash
        foreach (Algorithm::toArray() as $algorithm) {
            $this->integrityService->computeHash($content, $algorithm);
        }
    }
}