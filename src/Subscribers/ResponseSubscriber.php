<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\RouteService;
use OSW3\Api\Service\ConfigurationService;
use OSW3\Api\Service\ExecutionTimeService;
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
        private readonly RouteService $routeService,
        private readonly ExecutionTimeService $timer,
        private readonly ConfigurationService $configuration,
    ){}
    
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => ['onResponse', -10]];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $context = $this->routeService->getContext();
        
        if (in_array($context['endpoint'], ['register', 'login'], true)) {
            return;
        }


        // dump($event->getResponse()->getStatusCode());
        // dump($event->getResponse()->getMessage());
        // dd($event->getResponse());

        // dd([
        //     __CLASS__, 
        //     "TSE" => "{$this->timer->getDuration()} {$this->timer->getUnit()}",
        //     $context,
        //     $event->getResponse()->headers->all(),
        // ]);
        
        // dump([
        //     "CONTEXT",
        //     "context" => $context,
        //     "providers" => $this->configuration->getProviders(),
        //     "provider" => $this->configuration->getProvider($context['provider']),
        // ]);
        
        // dump([
        //     "PROVIDERS",
        //     "context"      => $context,
        //     "providers"    => $this->configuration->getProviders(),
        //     "provider"     => $this->configuration->getProvider($context['provider']),
        //     "has_provider" => $this->configuration->hasProvider($context['provider']),
        // ]);
        
        // dump([
        //     "COLLECTIONS",
        //     "collections"    => $this->configuration->getCollections($context['provider']),
        //     "collection"     => $this->configuration->getCollection($context['provider'], $context['collection']),
        //     "has_collection" => $this->configuration->hasCollection($context['provider'], $context['collection']),
        // ]);
        
        // dump([
        //     "ENDPOINTS",
        //     "endpoints"    => $this->configuration->getEndpoints($context['provider'], $context['collection']),
        //     "endpoint"     => $this->configuration->getEndpoint($context['provider'], $context['collection'], $context['endpoint']),
        //     "has_endpoint" => $this->configuration->hasEndpoint($context['provider'], $context['collection'], $context['endpoint']),
        // ]);
        
        // dump([
        //     "DOCUMENTATION",
        //     "documentation_enabled" => $this->configuration->isDocumentationEnabled($context['provider']),
        // ]);
        
        // dump([
        //     "VERSIONING",
        //     "version_number"        => $this->configuration->getVersionNumber($context['provider']),
        //     "version_prefix"        => $this->configuration->getVersionPrefix($context['provider']),
        //     "version_location"      => $this->configuration->getVersionLocation($context['provider']),
        //     "version_header_format" => $this->configuration->getVersionHeaderFormat($context['provider']),
        //     "version_is_beta"       => $this->configuration->isVersionBeta($context['provider']),
        //     "version_is_deprecated" => $this->configuration->isVersionDeprecated($context['provider']),
        // ]);
        
        // dump([
        //     "ROUTE (name)",
        //     "route_pattern_provider"   => $this->configuration->getRouteNamePattern($context['provider']),
        //     "route_pattern_collection" => $this->configuration->getRouteNamePattern($context['provider'], $context['collection']),
        //     "endpoint_route_name"      => $this->configuration->getRouteNamePattern($context['provider'], $context['collection'], $context['endpoint']),

        //     "ROUTE (path)",
        //     "route_prefix_provider"   => $this->configuration->getRoutePrefix($context['provider']),
        //     "route_prefix_collection" => $this->configuration->getRoutePrefix($context['provider'], $context['collection']),
        //     "endpoint_route_prefix"   => $this->configuration->getRoutePrefix($context['provider'], $context['collection'], $context['endpoint']),

        //     "ROUTE (hosts & scheme)",
        //     "route_host_provider"   => $this->configuration->getRouteHosts($context['provider']),
        //     "route_scheme_provider" => $this->configuration->getRouteSchemes($context['provider']),
        // ]);
        
        // dump([
        //     "SEARCH",
        //     "is_global_search_enabled"     => $this->configuration->isSearchEnabled($context['provider']),
        //     "is_collection_search_enabled" => $this->configuration->isSearchEnabled($context['provider'], $context['collection']),
        //     "collection_search_fields"     => $this->configuration->getSearchFields($context['provider'], $context['collection']),
        // ]);
        
        // dump([
        //     "DEBUG",
        //     "is_debug_enabled"           => $this->configuration->isDebugEnabled($context['provider']),
        //     "is_tracing_enabled"         => $this->configuration->isTracingEnabled($context['provider']),
        //     "is_tracing_request_enabled" => $this->configuration->isTracingIdRequestEnabled($context['provider']),
        // ]);
        
        // dump([
        //     "PAGINATION",
        //     "is_global_pagination_enabled"      => $this->configuration->isPaginationEnabled($context['provider']),
        //     "pagination_limit"                  => $this->configuration->getPaginationLimit($context['provider'], $context['collection']),
        //     "pagination_limit_max"              => $this->configuration->getPaginationMaxLimit($context['provider']),
        //     "pagination_limit_override_allowed" => $this->configuration->isPaginationLimitOverrideAllowed($context['provider']),
        // ]);
        
        // dump([
        //     "URL SUPPORT",
        //     "has_url_support"   => $this->configuration->hasUrlSupport($context['provider']),
        //     "is_url_absolute"   => $this->configuration->isUrlAbsolute($context['provider']),
        //     "url_property_name" => $this->configuration->getUrlProperty($context['provider']),
        // ]);
        
        // dump([
        //     "TEMPLATES",
        //     "template_list"       => $this->configuration->getListTemplate($context['provider']),
        //     "template_item"       => $this->configuration->getItemTemplate($context['provider']),
        //     "template_error"      => $this->configuration->getErrorTemplate($context['provider']),
        //     "template_no_content" => $this->configuration->getNoContentTemplate($context['provider']),
        //     "response_format"     => $this->configuration->getResponseFormat($context['provider']),
        // ]);
        
        // dump([
        //     "RESPONSES",
        //     "response_headers"       => $this->configuration->getResponseHeaders($context['provider']),
        //     "response_cache_control" => $this->configuration->getResponseCacheControl($context['provider']),
        //     "response_cache_public"  => $this->configuration->isResponseCachePublic($context['provider']),
        //     "response_cache_no_store" => $this->configuration->getResponseCacheControlNoStore($context['provider']),
        //     "response_cache_must_revalidate" => $this->configuration->getResponseCacheControlMustRevalidate($context['provider']),
        //     "response_cache_max_age" => $this->configuration->getResponseCacheControlMaxAge($context['provider']),
        //     "response_hashing_algorithm" => $this->configuration->getResponseHashingAlgorithm($context['provider']),
        // ]);
        
        // dump([
        //     "SECURITY",
        //     "security_entity_class"    => $this->configuration->getSecurityEntityClass($context['provider']),
        //     "security_collection_name" => $this->configuration->getSecurityCollectionName($context['provider']),
            
        //     "REGISTRATION",
        //     "is_registration_enabled"   => $this->configuration->isRegistrationEnabled($context['provider']),
        //     "registration_route_method" => $this->configuration->getRegistrationMethod($context['provider']),
        //     "registration_route_path"   => $this->configuration->getRegistrationPath($context['provider']),
        //     "registration_controller"   => $this->configuration->getRegistrationController($context['provider']),
        //     "registration_properties"   => $this->configuration->getRegistrationProperties($context['provider']),
            
        //     "LOGIN",
        //     "is_login_enabled"        => $this->configuration->isLoginEnabled($context['provider']),
        //     "login_route_method"      => $this->configuration->getLoginMethod($context['provider']),
        //     "login_route_path"        => $this->configuration->getLoginPath($context['provider']),
        //     "login_controller"        => $this->configuration->getLoginController($context['provider']),
        //     "login_properties"        => $this->configuration->getLoginProperties($context['provider']),
        // ]);
        
        // dump([
        //     "RATE LIMITING",
        //     "rate_limit_enabled"       => $this->configuration->isRateLimitEnabled($context['provider']),
        //     // "rate_limit_max_requests"  => $this->configuration->getRateLimitMaxRequests($context['provider']),
        //     // "rate_limit_window"        => $this->configuration->getRateLimitWindow($context['provider']),
        // ]);
        
        // dd('');
    }


    //     $endpointRoute = $this->configuration->getEndpointRoute('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($endpointRoute);
    //     $endpointRouteName = $this->configuration->getEndpointRouteName('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($endpointRouteName);
    //     $endpointRouteMethods = $this->configuration->getEndpointRouteMethods('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($endpointRouteMethods);
    //     $endpointRouteController = $this->configuration->getEndpointRouteController('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($endpointRouteController);
    //     $endpointRouteOptions = $this->configuration->getEndpointRouteOptions('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($endpointRouteOptions);
    //     $endpointRouteCondition = $this->configuration->getEndpointRouteCondition('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($endpointRouteCondition);
    //     $endpointRouteRequirements = $this->configuration->getEndpointRouteRequirements('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($endpointRouteRequirements);
        

    //     $repositoryInstance = $this->configuration->getRepository('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($repositoryInstance);
    //     $method = $this->configuration->getMethod('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($method);
    //     $criteria = $this->configuration->getCriteria('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($criteria);
    //     $orderBy = $this->configuration->getOrderBy('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($orderBy);
    //     $limit = $this->configuration->getLimit('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($limit);
    //     $fetchMode = $this->configuration->getFetchMode('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($fetchMode);

        
    //     $metadata = $this->configuration->getMetadata('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($metadata);
        

    //     $granted = $this->configuration->getGranted('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($granted);
    //     $voter = $this->configuration->getVoter('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($voter);
        

    //     $hooks = $this->configuration->getHooks('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($hooks);
        

    //     $serializeGroups = $this->configuration->getSerializerGroups('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($serializeGroups);
    //     $serializeTransformer = $this->configuration->getSerializerTransformer('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($serializeTransformer);
        

    //     $transformer = $this->configuration->getTransformer('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($transformer);
        

    //     $rateLimit = $this->configuration->getRateLimit('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($rateLimit);
    //     $rateLimitByRole = $this->configuration->getRateLimitByRole('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($rateLimitByRole);
    //     $rateLimitByUser = $this->configuration->getRateLimitByUser('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($rateLimitByUser);
        

    //     dd('');
    // }
}