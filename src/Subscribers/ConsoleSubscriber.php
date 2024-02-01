<?php 
namespace OSW3\Api\Subscribers;

use OSW3\Api\Services\RouteService;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleSubscriber implements EventSubscriberInterface 
{
    public function __construct(
        private RouteService $routeService,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'onConsoleCommand',
        ];
    }
    
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $this->routeService->addCollection();
    }
}