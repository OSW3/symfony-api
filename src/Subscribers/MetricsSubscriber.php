<?php 
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\ExecutionTimeService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles metrics-related tasks during the request lifecycle.
 * 
 * @stage 5
 * @priority 0
 * @before -
 * @after ResponseSubscriber
 */
class MetricsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ExecutionTimeService $timer,
    ){}
    
    public static function getSubscribedEvents(): array
    {
        return [
            // High priority to log the start early
            KernelEvents::REQUEST => ['onRequest', 1000],

            // Low priority to log the end late, just before ResponseSubscriber
            // KernelEvents::RESPONSE => ['onResponse', 0], 
            KernelEvents::RESPONSE => ['onResponse', -9], 
        ];
    }

    public function onRequest(): void
    {
        // Start the execution timer
        $this->timer->start();
    }

    public function onResponse(): void
    {
        // Stop the execution timer
        $this->timer->stop();
    }
}