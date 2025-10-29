<?php 
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\RouteService;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\SupportService;
use OSW3\Api\Service\ResponseService;
use OSW3\Api\Service\TemplateService;
use OSW3\Api\Service\SerializeService;
use OSW3\Api\Service\RepositoryService;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpKernel\KernelEvents;
use OSW3\Api\Exception\RepositoryCallException;
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
        private readonly RouteService $routeService,
        private readonly SupportService $supportService,
        private readonly ResponseService $responseService,
        private readonly TemplateService $templateService,
        private readonly SerializeService $serializeService,
        private readonly RepositoryService $repositoryService,

        private readonly ContextService $contextService,
        private readonly RequestService $requestService,
        private readonly ConfigurationService $configurationService,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 0]];
    }
    
    public function onRequest(RequestEvent $event): void 
    {
        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();
        $security   = $this->configurationService->getSecurity($provider);
        $routeName  = $event->getRequest()->attributes->get('_route');

        $securityEndpoints = array_keys(array_merge(
            $security['registration'] ?? [], 
            $security['authentication'] ?? [], 
            $security['password'] ?? [], 
        ));

        // Support
        // -- 

        // Is _route parameter is defined ?
        if (!$routeName) {
            return;
        }

        // Is main request
        if (!$event->isMainRequest()) {
            return;
        }

        // Bypass for Security.registration and Security.authentication endpoints
        if (in_array($endpoint, $securityEndpoints, true)) {
            return;
        }

        // Is _route parameter is defined ?
        if (!$routeName) {
            return;
        }
        
        // Is route is defined in the config
        if (!$this->routeService->isRegisteredRoute($routeName)) {
            return;
        }

        // Is the HTTP Method supported
        if (!$this->routeService->isMethodSupported($routeName)) {
            return;
        }

        // Is the collection repository callable
        if (!$this->repositoryService->isRepositoryCallable()) {
            $repository = $this->repositoryService->getRepositoryClass();
            $method     = $this->repositoryService->getRepositoryMethod();
            throw RepositoryCallException::invalid($repository, $method);
        }

        // Has requirements params
        if (!$this->requestService->hasRequiredParameters()) {
            return;
        }

        // Check if a custom controller is defined for the route
        if ($event->getRequest()->attributes->get('_controller')) {
            return;
        }


        // Processing
        // -- 

        // Retrieve and normalize data from the repository
        $raw        = $this->repositoryService->execute();
        $normalized = $this->serializeService->normalize($raw);
        $this->responseService->setData($normalized);


        // Determine response type (list or item)
        // $request = $event->getRequest();
        // $routeParams = $request->attributes->get('_route_params', []);
        // $hasId = isset($routeParams['id']) || isset($normalized['id']);

        // $type = match (true) {
        //     $request->isMethod('GET') && $hasId => 'item',
        //     $request->isMethod('GET') => 'list',
        //     in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'], true) => 'item',
        //     default => 'list',
        // };


        // Prepare the response using templates
        $type     = is_array($normalized) && array_is_list($normalized) && count($normalized) ? 'list' : 'item';
        $template = $this->templateService->getTemplate($type);
        $content  = $this->templateService->parse($template, $normalized);
        $this->responseService->setContent($content);

        // Set the response in the event
        $event->setResponse($this->responseService->build());
    }
}