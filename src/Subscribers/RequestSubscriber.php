<?php 
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\SupportService;
use OSW3\Api\Service\ResponseService;
use OSW3\Api\Service\TemplateService;
use OSW3\Api\Service\SerializeService;
use OSW3\Api\Service\RepositoryService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles request-related tasks during the request lifecycle.
 * 
 * @stage 3
 * @priority 0
 * @before ResponseSubscriber
 * @after LocaleSubscriber
 */
class RequestSubscriber implements EventSubscriberInterface 
{
    public function __construct(
        private readonly RepositoryService $repositoryService,
        private readonly SupportService $supportService,
        private readonly ResponseService $responseService,
        private readonly SerializeService $serializeService,
        private readonly TemplateService $templateService,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 0]];
    }
    
    public function onRequest(RequestEvent $event): void 
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Check if the request is not a valid defined route
        if (!$this->supportService->supports()) {
            return;
        }

        // Check if a custom controller is defined for the route
        if ($event->getRequest()->attributes->get('_controller')) {
            return;
        }

        $rawData        = $this->repositoryService->execute();
        $normalizedData = $this->serializeService->normalize($rawData);
        $template       = $this->templateService->getTemplate('list');
        $responseData   = $this->templateService->parse($template, $normalizedData);
        $response       = $this->responseService->build($responseData);
        $event->setResponse($response);
    }
}