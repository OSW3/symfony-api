<?php
namespace OSW3\Api\Subscribers;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class LoggerSubscriber implements EventSubscriberInterface
{
    public function __construct(){}

    public static function getSubscribedEvents(): array
    {
        return [
            // Lowest priority to log the end after all other subscribers
            // KernelEvents::TERMINATE => ['onTerminate'],
        ];
    }

    public function onTerminate(): void
    {
    }
}