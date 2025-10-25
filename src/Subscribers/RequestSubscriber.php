<?php 
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\ConfigurationService;
use OSW3\Api\Service\SupportService;
use OSW3\Api\Service\ResponseService;
use OSW3\Api\Service\TemplateService;
use OSW3\Api\Service\SerializeService;
use OSW3\Api\Service\RepositoryService;
use OSW3\Api\Service\RouteService;
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
        private readonly RouteService $routeService,
        private readonly SupportService $supportService,
        private readonly ResponseService $responseService,
        private readonly TemplateService $templateService,
        private readonly SerializeService $serializeService,
        private readonly RepositoryService $repositoryService,
        
        private readonly ConfigurationService $configurationService,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 0]];
    }
    
    public function onRequest(RequestEvent $event): void 
    {
        $provider = $this->configurationService->getContext('provider');
        $collection = $this->configurationService->getContext('collection');
        $endpoint = $this->configurationService->getContext('endpoint');


        dump( $this->configurationService->getSerializerGroups($provider, $collection, $endpoint) );
        dump( $this->configurationService->getSerializerIgnore($provider, $collection, $endpoint) );
        dump( $this->configurationService->getSerializerDatetimeFormat($provider) );
        dump( $this->configurationService->getSerializerTimezone($provider) );
        dump( $this->configurationService->getSerializerSkipNull($provider) );
        dd( $this->configurationService->getSerializerTransformer($provider, $collection, $endpoint) );

        // dump( $this->configurationService->getRoute($provider) );
        // dump( $this->configurationService->getRoute($provider, $collection) );
        // dd( $this->configurationService->getRoute($provider, $collection, $endpoint) );



        if (!$event->isMainRequest()) {
            return;
        }

        $context = $this->routeService->getContext();
        
        if (in_array($context['endpoint'], ['register', 'login'], true)) {
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