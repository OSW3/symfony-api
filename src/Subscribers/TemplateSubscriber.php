<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\AppService;
use OSW3\Api\Service\DebugService;
use OSW3\Api\Service\ClientService;
use OSW3\Api\Service\ServerService;
use OSW3\Api\Builder\OptionsBuilder;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\VersionService;
use OSW3\Api\Service\ResponseService;
use OSW3\Api\Service\SecurityService;
use OSW3\Api\Service\TemplateService;
use OSW3\Api\Service\IntegrityService;
use OSW3\Api\Service\RateLimitService;
use OSW3\Api\Service\PaginationService;
use OSW3\Api\Service\ExecutionTimeService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TemplateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AppService $appService,
        private readonly ClientService $clientService,
        private readonly ServerService $serverService,
        private readonly ContextService $contextService,
        private readonly RequestService $requestService,
        private readonly VersionService $versionService,
        private readonly SecurityService $securityService,
        private readonly TemplateService $templateService,
        private readonly RateLimitService $rateLimitService,
        private readonly PaginationService $paginationService,
        private readonly DebugService $debugService,
        private readonly ResponseService $responseService,
        private readonly ExecutionTimeService $timerService,
        private readonly IntegrityService $integrityService,
        private readonly OptionsBuilder $optionsBuilder,
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