<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\AppService;
use OSW3\Api\Service\DebugService;
use OSW3\Api\Service\RouteService;
use OSW3\Api\Service\ClientService;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\HeadersService;
use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\PaginationService;
use OSW3\Api\Service\ConfigurationService;
use OSW3\Api\Service\DocumentationService;
use OSW3\Api\Service\RateLimitService;
use OSW3\Api\Service\RepositoryService;
use OSW3\Api\Service\ResponseStatusService;
use OSW3\Api\Service\SecurityService;
use OSW3\Api\Service\SerializeService;
use OSW3\Api\Service\ServerService;
use OSW3\Api\Service\VersionService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class DEV_Subscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AppService $appService,
        private readonly ClientService $clientService,
        private readonly ContextService $contextService,
        private readonly DebugService $debugService,
        private readonly DocumentationService $documentationService,
        private readonly HeadersService $headersService,
        private readonly PaginationService $paginationService,
        private readonly RateLimitService $rateLimitService,
        private readonly RepositoryService $repositoryService,
        private readonly RequestService $requestService,
        private readonly ResponseStatusService $responseStatusService,
        private readonly RouteService $routeService,
        private readonly SecurityService $securityService,
        private readonly SerializeService $serializeService,
        private readonly ServerService $serverService,
        private readonly VersionService $versionService,

        private readonly ?TranslatorInterface $translator,
        private readonly ConfigurationService $configuration,
        private readonly AuthorizationCheckerInterface $auth,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            // KernelEvents::REQUEST => ['onRequest', 0]
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();


        dump('DEV SUBSCRIBER');

        // VERSION INFO
        dump('VERSION INFO:',[
            'version_number'                => $this->versionService->getNumber(),
            'version_prefix'                => $this->versionService->getPrefix(),
            'all_versions'                  => $this->versionService->getAllVersions(),
            'supported_versions'            => $this->versionService->getSupportedVersions(),
            'deprecated_versions'           => $this->versionService->getDeprecatedVersions(),
            'current_version_label'         => $this->versionService->getLabel(),
            'is_current_version_beta'       => $this->versionService->isBeta(),
            'is_current_version_deprecated' => $this->versionService->isDeprecated(),
        ]);

        // SERVER INFO
        dump('SERVER INFO:',[
            'ip'                 => $this->serverService->getIp(),
            'hostname'           => $this->serverService->getHostname(),
            'environment'        => $this->serverService->getEnvironment(),
            'php_version'        => $this->serverService->getPhpVersion(),
            'symfony_version'    => $this->serverService->getSymfonyVersion(),
            'name'               => $this->serverService->getName(),
            'software'           => $this->serverService->getSoftware(),
            'software_version'   => $this->serverService->getSoftwareVersion(),
            'software_release'   => $this->serverService->getSoftwareRelease(),
            'os'                 => $this->serverService->getOs(),
            'os_version'         => $this->serverService->getOsVersion(),
            'os_release'         => $this->serverService->getOsRelease(),
            'date'               => $this->serverService->getDate(),
            'time'               => $this->serverService->getTime(),
            'timezone'           => $this->serverService->getTimezone(),
            'uptime'             => $this->serverService->getUptime(),
            'load_average'       => $this->serverService->getLoadAverage(),
            'memory_limit'       => $this->serverService->getMemoryLimit(),
            'free_memory'        => $this->serverService->getFreeMemory(),
            'available_memory'   => $this->serverService->getAvailableMemory(),
            'used_memory'        => $this->serverService->getUsedMemory(),
            'memory_usage'       => $this->serverService->getMemoryUsage(),
            'total_disk'         => $this->serverService->getTotalDisk(),
            'free_disk'          => $this->serverService->getFreeDisk(),
            'used_disk'          => $this->serverService->getUsedDisk(),
            'disk_usage'         => $this->serverService->getDiskUsage(),
            'db_driver'          => $this->serverService->getDatabaseDriver(),
            'db_version'         => $this->serverService->getDatabaseVersion(),
            'cpu_model'          => $this->serverService->getCpuModel(),
            'cpu_cores'          => $this->serverService->getCpuCores(),
            'cpu_speed'          => $this->serverService->getCpuSpeed(),
            'cpu_threads'        => $this->serverService->getCpuThreads(),
            'cpu_physical_cores' => $this->serverService->getCpuPhysicalCores(),
            'cpu_logical_cores'  => $this->serverService->getCpuLogicalCores(),
            'cpu_cache'          => $this->serverService->getCpuCache(),
            'architecture'       => $this->serverService->getArchitecture(),
            'kernel_version'     => $this->serverService->getKernelVersion(),
            'document_root'      => $this->serverService->getDocumentRoot(),
            
        ]);

        // SERIALIZATION INFO
        dump('SERIALIZATION INFO:',[
            'encoder'           => $this->serializeService->getEncoder(),
            'groups'            => $this->serializeService->getGroups(),
            'ignored_attributes'=> $this->serializeService->getIgnoredAttributes(),
            'datetime_format'   => $this->serializeService->getDatetimeFormat(),
            'timezone'          => $this->serializeService->getTimezone(),
            'skip_null'         => $this->serializeService->isSkipNull(),
            'has_url_support'   => $this->serializeService->hasUrlSupport(),
        ]);

        // SECURITY INFO
        dump('SECURITY INFO:',[
            'user'             => $this->securityService->getUser(),
            'is_authenticated' => $this->securityService->isAuthenticated(),
            'user_id'          => $this->securityService->getId(),
            'user_name'        => $this->securityService->getUsername(),
            'user_identifier'  => $this->securityService->getIdentifier(),
            'user_roles'       => $this->securityService->getRoles(),
            'user_has_role'    => $this->securityService->hasRole('ROLE_ADMIN'),
            'user_permissions' => $this->securityService->getPermissions(),
            'is_mfa_enabled'   => $this->securityService->isMfaEnabled(),
        ]);

        // ROUTE INFO
        dump('ROUTE INFO:',[
            'exposed_routes' => $this->routeService->getExposedRoutes(),
            'current_route'  => $this->routeService->getCurrentRoute(),
            'context'        => $this->routeService->getContext(),
            'name'           => $this->routeService->getName($provider, $collection, $endpoint),
            'path'           => $this->routeService->getPath($provider, $collection, $endpoint),
            'defaults'       => $this->routeService->getDefaults($provider, $collection, $endpoint),
            'options'        => $this->routeService->getOptions($provider, $collection, $endpoint),
            'methods'        => $this->routeService->getMethods($provider, $collection, $endpoint),
            'requirements'   => $this->routeService->getRequirements($provider, $collection, $endpoint),
            'host'           => $this->routeService->getHosts($provider, $collection, $endpoint),
            'schemes'        => $this->routeService->getSchemes($provider, $collection, $endpoint),
            'condition'      => $this->routeService->getCondition($provider, $collection, $endpoint),
            'controller'     => $this->routeService->getController($provider, $collection, $endpoint),
        ]);

        // RESPONSE STATUS INFO
        dump('RESPONSE STATUS INFO:',[
            'status_code' => $this->responseStatusService->getStatusCode(),
            'status_text' => $this->responseStatusService->getStatusText(),
            'state' => $this->responseStatusService->getState(),
            'is_success' => $this->responseStatusService->isSuccess(),
            'is_failed' => $this->responseStatusService->isFailed(),
            'is_error' => $this->responseStatusService->isError(),
        ]);

        // REQUEST INFO
        dump('REQUEST INFO:',[
            'current_request'       => $this->requestService->getCurrentRequest(),
            'current_route'         => $this->requestService->getCurrentRoute(),
            'is_ajax'               => $this->requestService->isAjax(),
            'is_from_trusted_proxy' => $this->requestService->isFromTrustedProxy(),
            'http_method'           => $this->requestService->getMethod(),
            'scheme'                => $this->requestService->getScheme(),
            'is_secure'             => $this->requestService->isSecure(),
            'base_url'              => $this->requestService->getBaseUrl(),
            'port'                  => $this->requestService->getPort(),
            'uri'                   => $this->requestService->getUri(),
            'path_info'             => $this->requestService->getPathInfo(),
            'params'                => $this->requestService->getParameters(),
            'query_parameters'      => $this->requestService->getQueryParameters(),
            'request_parameters'    => $this->requestService->getRequestParameters(),
            'attributes_parameters' => $this->requestService->getAttributesParameters(),
            'has_required_params'   => $this->requestService->hasRequiredParameters(),
            'locale'                => $this->requestService->getLocale(),
            'headers'               => $this->requestService->getHeaders(),
            'raw_content'           => $this->requestService->getRawContent(),
            'format'                => $this->requestService->getFormat(),
        ]);

        // REPOSITORY INFO
        dump('REPOSITORY INFO:',[
            'repository_class' => $this->repositoryService->getRepositoryClass(),
            'repository_method' => $this->repositoryService->getRepositoryMethod(),
            'repository_instance' => $this->repositoryService->getRepositoryInstance(),
            'is_callable' => $this->repositoryService->isRepositoryCallable(),
            
        ]);

        // RATE LIMIT INFO
        dump('RATE LIMIT INFO:',[
            'is_enabled'           => $this->rateLimitService->isEnabled(),
            'default_limit'        => $this->rateLimitService->getDefaultLimit(),
            'limit_by_role'        => $this->rateLimitService->getLimitByRole(),
            'limit_by_user'        => $this->rateLimitService->getLimitByUser(),
            'limit_by_ip'          => $this->rateLimitService->getLimitByIp(),
            'limit_by_application' => $this->rateLimitService->getLimitByApplication(),
            'limit'                => $this->rateLimitService->getLimit(),
            'used'                 => $this->rateLimitService->getUsed(),
            'remaining'            => $this->rateLimitService->getRemaining(),
            'reset'                => $this->rateLimitService->getReset(),
        ]);

        // PAGINATION INFO
        dump('PAGINATION INFO:',[
            'is_enabled'         => $this->paginationService->isEnabled(),
            'total_pages'        => $this->paginationService->getTotalPages(),
            'current_page'       => $this->paginationService->getPage(),
            'current_page_alias' => $this->paginationService->getCurrentPage(),
            'total_items'        => $this->paginationService->getTotal(),
            'total_items_alias'  => $this->paginationService->getTotalItems(),
            'items_per_page'     => $this->paginationService->getLimit(),
            'current_offset'     => $this->paginationService->getOffset(),
            'url_previous'       => $this->paginationService->getPrevious(),
            'url_next'           => $this->paginationService->getNext(),
            'url_self'           => $this->paginationService->getSelf(),
            'url_first'          => $this->paginationService->getFirst(),
            'url_last'           => $this->paginationService->getLast(),
            'is_first_page'      => $this->paginationService->isFirstPage(),
            'is_last_page'       => $this->paginationService->isLastPage(),
            'has_prev_page'      => $this->paginationService->hasPreviousPage(),
            'has_next_page'      => $this->paginationService->hasNextPage(),
        ]);

        // HEADERS INFO
        dump('HEADERS INFO:',[
            'merge_strategy'      => $this->headersService->mergeStrategy(),
            'strip_x_prefix'      => $this->headersService->stripXPrefix(),
            'keep_legacy'         => $this->headersService->keepLegacy(),
            'directives_exposed'  => $this->headersService->getDirectives('exposed'),
            'directives_vary'     => $this->headersService->getDirectives('vary'),
            'directives_custom'   => $this->headersService->getDirectives('custom'),
            'directives_remove'   => $this->headersService->getDirectives('remove'),

            'format'              => $this->headersService->getFormat(),
            'content_type'        => $this->headersService->getContentType(),

            'resolved_headers'    => $this->headersService->resolveHeadersValue(),
        ]);

        // DOCUMENTATION INFO
        dump('DOCUMENTATION INFO:',[
            'is_enabled'           => $this->documentationService->isEnabled(),
            'url'                  => $this->documentationService->getUrl(),
        ]);

        // DEBUG INFO
        dump('DEBUG INFO:',[
            'is_enabled'           => $this->debugService->isEnabled(),
            'memory_usage'         => $this->debugService->getMemoryUsage(),
            'memory_peak'          => $this->debugService->getMemoryPeak(),
            'log_level'            => $this->debugService->getLogLevel(),
            'included_files_count' => $this->debugService->getCountIncludedFiles(),
            'included_files'       => $this->debugService->getIncludedFiles(),
        ]);

        // CONTEXT INFO
        dump('CONTEXT INFO:',[
            'context_environment' => $this->contextService->getEnvironment(),
            'context_debug' => $this->contextService->isDebug(),
            'context_root_dir' => $this->contextService->getBundleDir(),
            'context_project_dir' => $this->contextService->getProjectDir(),
            'context_provider' => $this->contextService->getProvider(),
            'context_collection' => $this->contextService->getCollection(),
            'context_endpoint' => $this->contextService->getEndpoint(),
        ]);
        
        // CLIENT INFO
        dump('CLIENT INFO:',[
            'client_ip' => $this->clientService->getIp(),
            'client_ips' => $this->clientService->getIps(),
            'client_user_agent' => $this->clientService->getUserAgent(),
            'client_charset' => $this->clientService->getCharsets(),
            'client_accept_language' => $this->clientService->getAcceptableContentTypes(),
            'client_encodings' => $this->clientService->getEncodings(),
            'client_device' => $this->clientService->getDevice(),
            'client_is_mobile' => $this->clientService->isMobile(),
            'client_is_tablet' => $this->clientService->isTablet(),
            'client_is_desktop' => $this->clientService->isDesktop(),   
            'client_browser' => $this->clientService->getBrowser(),
            'client_browser_version' => $this->clientService->getBrowserVersion(),
            'client_browser_version_major' => $this->clientService->getBrowserVersionMajor(),
            'client_browser_version_minor' => $this->clientService->getBrowserVersionMinor(),
            'client_browser_version_patch' => $this->clientService->getBrowserVersionPatch(),
            'client_os' => $this->clientService->getOs(),
            'client_os_version' => $this->clientService->getOsVersion(),
            'client_os_version_major' => $this->clientService->getOsVersionMajor(),
            'client_os_version_minor' => $this->clientService->getOsVersionMinor(),
            'client_os_version_patch' => $this->clientService->getOsVersionPatch(),
            'client_engine' => $this->clientService->getEngine(),
            'client_languages' => $this->clientService->getLanguages(),
            'client_language' => $this->clientService->getLanguage(),
            'client_fingerprint' => $this->clientService->getFingerprint(),
        ]);

        // APP INFO
        dump('APP INFO:',[
            'app_name' => $this->appService->getName(),
            'app_vendor' => $this->appService->getVendor(),
            'app_version' => $this->appService->getVersion(),
            'app_description' => $this->appService->getDescription(),
            'app_license' => $this->appService->getLicense(),
        ]);

        dd('END');
    }
}