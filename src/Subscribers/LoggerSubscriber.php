<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\ConfigurationService;
use OSW3\Api\Service\ExecutionTimeService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles logging of execution time during the request lifecycle.
 * 
 * @stage 0
 * @priority 0
 * @before ResponseSubscriber
 * @after -
 */
class LoggerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ExecutionTimeService $timer,
        private readonly ConfigurationService $configuration,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            // High priority to log the start early
            // KernelEvents::REQUEST => ['onRequest', 1000],

            // Low priority to log the end late, just before ResponseSubscriber
            // KernelEvents::RESPONSE => ['onResponse', -9], 

            // Lowest priority to log the end after all other subscribers
            KernelEvents::TERMINATE => ['onTerminate'],
        ];
    }

    // public function onRequest(): void
    // {
    //     // Start the execution timer
    //     $this->timer->start();
    // }

    // public function onResponse(): void
    // {
    //     // Stop the execution timer
    //     $this->timer->stop();
    // }

    public function onTerminate(): void
    {
        // Log the total execution time
        $duration = $this->timer->getDuration();
        $unit = $this->timer->getUnit();
        // Here you would typically log this information to a file or monitoring system
        dump("Total Execution Time: {$duration} {$unit}");
    }
}