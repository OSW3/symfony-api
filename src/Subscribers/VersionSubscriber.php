<?php 
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\RouteService;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\HeadersService;
use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\SupportService;
use OSW3\Api\Service\VersionService;
use OSW3\Api\Service\ResponseService;
use OSW3\Api\Service\TemplateService;
use OSW3\Api\Service\SerializeService;
use OSW3\Api\Service\PaginationService;
use OSW3\Api\Service\RepositoryService;
use OSW3\Api\Service\DeprecationService;
use OSW3\Api\Service\ConfigurationService;
use OSW3\Api\Service\ResponseStatusService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use OSW3\Api\Exception\RepositoryCallException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class VersionSubscriber implements EventSubscriberInterface 
{
    public function __construct(
        private readonly VersionService $versionService,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', 0]
        ];
    }
    
    public function onResponse(ResponseEvent $event): void 
    {
        // Is main request
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();


        if ($this->versionService->getLocation() === 'header') 
        {
            $response->headers->set(
                $this->versionService->getHeaderDirective(),
                $this->versionService->getHeaderPattern(),
            );
        }

    }
}