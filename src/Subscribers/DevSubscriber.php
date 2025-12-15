<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\CorsService;
use OSW3\Api\Service\RouteService;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\VersionService;
use OSW3\Api\Service\EndpointService;
use OSW3\Api\Service\ProviderService;
use OSW3\Api\Service\ResponseService;
use OSW3\Api\Service\TemplateService;
use OSW3\Api\Service\IntegrityService;
use OSW3\Api\Service\RateLimitService;
use OSW3\Api\Service\CollectionService;
use OSW3\Api\Service\PaginationService;
use OSW3\Api\Service\UrlSupportService;
use OSW3\Api\Service\DeprecationService;
use OSW3\Api\Service\CacheControlService;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DevSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ContextService $contextService,
        private readonly ProviderService $providerService,
        private readonly CollectionService $collectionService,
        private readonly EndpointService $endpointService,
        
        private readonly DeprecationService $deprecationService,
        private readonly VersionService $versionService,
        private readonly RouteService $routeService,
        private readonly PaginationService $paginationService,
        private readonly UrlSupportService $urlSupportService,
        private readonly RateLimitService $rateLimitService,
        private readonly TemplateService $templateService,
        private readonly ResponseService $responseService,
        private readonly IntegrityService $integrityService,
        private readonly CacheControlService $cacheControlService,
        private readonly CorsService $corsService,

        private readonly ConfigurationService $configurationService,
    ){}
    
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', -9],
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        $provider   = $this->contextService->getProvider();
        $segment    = $this->contextService->getSegment();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();

        // -- CORS
        dd(
            $this->corsService->isEnabled(),
            $this->corsService->getOrigins(),
            $this->corsService->getMethods(),
            $this->corsService->getHeaders(),
            $this->corsService->getExposedHeaders(),
            $this->corsService->exposeCredentials(),
            $this->corsService->getMaxAge(),
        );

        // -- CACHE CONTROL
        dd(
            $this->cacheControlService->isEnabled(),
            $this->cacheControlService->isPublic(),
            $this->cacheControlService->isNoStore(),
            $this->cacheControlService->isMustRevalidate(),
            $this->cacheControlService->getMaxAge(),
            $this->cacheControlService->toString(),
        );

        // -- HIGH JINKING PREVENTION
        dd(
        );

        // -- INTEGRITY
        dd(
            $this->integrityService->isEnabled(),
            $this->integrityService->getAlgorithm(),
            $this->integrityService->getHash( $this->integrityService->getAlgorithm() ),
        );

        // -- RESPONSE
        dd(
            $this->responseService->getFormat(),
            $this->responseService->getMimeType(),
            $this->responseService->getSize(),
            $this->responseService->getCount(),
            $this->responseService->isPrettyPrint(),
        );

        // -- TEMPLATES
        dd(
            $this->templateService->all(),
            $this->templateService->get('list'),
        );

        // -- RATE LIMITING
        dd(
            $this->rateLimitService->isEnabled(),
            $this->rateLimitService->getDefaultLimit(),
            $this->rateLimitService->getLimitByRole(),
            $this->rateLimitService->getLimitByUser(),
            $this->rateLimitService->getLimitByIp(),
            $this->rateLimitService->getLimitByApplication(),
            $this->rateLimitService->getLimit(),
            $this->rateLimitService->getRequestsLimit(),
            $this->rateLimitService->getPeriodLimit(),
            $this->rateLimitService->getUsed(),
            $this->rateLimitService->getRemaining(),
            $this->rateLimitService->getReset(),
        );

        // -- URL SUPPORT
        dd(
            $this->urlSupportService->isEnabled(),
            $this->urlSupportService->isAbsolute(),
            $this->urlSupportService->getProperty(),
        );
        
        // -- PAGINATION
        dd(
            $this->paginationService->isEnabled(),
            $this->paginationService->getParameterPage(),
            $this->paginationService->getParameterLimit(),
            $this->paginationService->getTotalPages(),
            $this->paginationService->getPage(),
            $this->paginationService->getPreviousPage(),
            $this->paginationService->getNextPage(),
            $this->paginationService->getTotal(),
            $this->paginationService->getTotalItems(),
            $this->paginationService->getLimit(),
            $this->paginationService->getDefaultLimit(),
            $this->paginationService->getMaxLimit(),
            $this->paginationService->getOffset(),
            $this->paginationService->getPreviousUrl(),
            $this->paginationService->getNextUrl(),
            $this->paginationService->getCurrentUrl(),
            $this->paginationService->getFirstUrl(),
            $this->paginationService->getLastUrl(),
            $this->paginationService->isFirstPage(),
            $this->paginationService->isLastPage(),
            $this->paginationService->hasPreviousPage(),
            $this->paginationService->hasNextPage(),
        );
        
        // -- ROUTES
        dd(
            $this->routeService->getPattern(),
            $this->routeService->getPrefix(),
            $this->routeService->getName(),
            $this->routeService->getRequirements(),
            $this->routeService->getOptions(),
            $this->routeService->getHosts(),
            $this->routeService->getSchemes(),
            $this->routeService->getMethods(),
            $this->routeService->getCondition(),
            $this->routeService->getDefaults(),
            $this->routeService->getExposedRoutes(),
            $this->routeService->getCurrentRoute(),
        );
        
        // -- VERSIONING
        dd([
            'mode'               => $this->versionService->getMode(),
            'number'             => $this->versionService->getNumber(),
            'prefix'             => $this->versionService->getPrefix(),
            'location'           => $this->versionService->getLocation(),
            'directive'          => $this->versionService->getDirective(),
            'pattern'            => $this->versionService->getPattern(),
            'beta'               => $this->versionService->isBeta(),
            
            'label'              => $this->versionService->getLabel(),
            'is_deprecated'      => $this->versionService->isDeprecated(),
            'all_version'        => $this->versionService->getAllVersions(),
            'supported_version'  => $this->versionService->getSupportedVersions(),
            'deprecated_version' => $this->versionService->getDeprecatedVersions(),
        ]);
        
        // -- DEPRECATION
        dd(
            $this->deprecationService->isEnabled(
                provider: 'my_custom_api_v1',
                fallbackOnCurrentContext: false
            ),
            $this->deprecationService->getStartAt(),
            $this->deprecationService->getSunsetAt(),
            $this->deprecationService->getLink(),
            $this->deprecationService->getSuccessor(),
            $this->deprecationService->getMessage(),
            $this->deprecationService->isActive(),
            $this->deprecationService->isDeprecated(),
            $this->deprecationService->isRemoved(),
            $this->deprecationService->getState(),
        );
        
        // -- ENDPOINTS
        dd(
            // $this->endpointService->all($provider, $segment, $collection),
            $this->endpointService->get($provider, $segment, $collection, $endpoint),
            $this->endpointService->exists($provider, $segment, $collection, $endpoint),
            $this->endpointService->isEnabled($provider, $segment, $collection, $endpoint),
        );

        //  -- COLLECTIONS
        dd(
            // $this->collectionService->all($provider, $segment),
            // $this->collectionService->get($provider, $segment, $collection),
            $this->collectionService->exists($provider, $segment, $collection),
            $this->collectionService->getName($provider, $segment, $collection),
            $this->collectionService->isEnabled($provider, $segment, $collection),
        );

        // -- PROVIDERS
        dd(
            // $this->providerService->all(),
            $this->providerService->names(),
            $this->providerService->count(),
            $this->providerService->get($provider),
            $this->providerService->has($provider),
            $this->providerService->isEnabled($provider),
        );

        // -- CONTEXT
        dd(
            $this->contextService->getProvider(),
            $this->contextService->getSegment(),
            $this->contextService->getCollection(),
            $this->contextService->getEndpoint(),
            $this->contextService->getEnvironment(),
            $this->contextService->isDebug(),
            $this->contextService->getBundleDir(),
            $this->contextService->getProjectDir(),
        );
    }
}