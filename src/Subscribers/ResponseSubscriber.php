<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\RouteService;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\HeadersService;
use OSW3\Api\Service\ResponseService;
use OSW3\Api\Service\ConfigurationService;
use OSW3\Api\Service\ExecutionTimeService;
use OSW3\Api\Service\ResponseStatusService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Handles response-related tasks during the request lifecycle.
 * 
 * @stage 4
 * @priority -10
 * @before -
 * @after RequestSubscriber
 */
class ResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        // private readonly RouteService $routeService,
        // private readonly ExecutionTimeService $timer,
        // private readonly ContextService $contextService,
        // private readonly ConfigurationService $configurationService,
        // private readonly ResponseService $responseService,
        private readonly HeadersService $headersService,
        private readonly ResponseStatusService $responseStatusService,
    ){}
    
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', -10],
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Get current response
        $response = $event->getResponse();
        

        // Get current headers
        $current  = $response->headers->all();

        foreach ($current as $name => $values) {
            $this->headersService->addHeader($name, $values);
        }
        
        // Build final headers
        $headers = $this->headersService->buildHeaders();


        // Set final status code
        $response->setStatusCode($this->responseStatusService->getStatusCode());

        // Reset existing headers
        $response->headers->replace([]);
        
        // Apply final headers to the response
        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }


        // $current  = $response->headers->all();
        // dd($current);
    }
}