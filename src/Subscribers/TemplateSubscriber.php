<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\AppService;
use OSW3\Api\Service\DebugService;
use OSW3\Api\Service\ClientService;
use OSW3\Api\Service\ServerService;
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

        // Get the template data
        $data = json_decode($content, true);

        // Get the template type
        $type = $this->templateService->getType();

        // Get the context provider
        $provider = $this->contextService->getProvider();

        // Determine the state from the status code
        $state = match (true) {
            $response->getStatusCode() >= 200 && $response->getStatusCode() < 300 => 'success',
            $response->getStatusCode() >= 400 && $response->getStatusCode() < 500 => 'failed',
            default                    => 'error',
        };

        // Get the integrity algorithm
        $algorithm = $this->integrityService->getAlgorithm();

        // Check if pagination is enabled
        $hasPagination = $this->paginationService->isEnabled();

        // Build the template options
        $options = [
            // App 
            'app.name'                     => $this->appService->getName(),
            'app.vendor'                   => $this->appService->getVendor(),
            'app.version'                  => $this->appService->getVersion(),
            'app.description'              => $this->appService->getDescription(),
            'app.license'                  => $this->appService->getLicense(),

            // Client
            'client.ip'                    => $this->clientService->getIp(),
            'client.user_agent'            => $this->clientService->getUserAgent(),
            'client.device'                => $this->clientService->getDevice(),
            'client.is_mobile'             => $this->clientService->isMobile(),
            'client.is_tablet'             => $this->clientService->isTablet(),
            'client.is_desktop'            => $this->clientService->isDesktop(),
            'client.browser'               => $this->clientService->getBrowser(),
            'client.browser_version'       => $this->clientService->getBrowserVersion(),
            'client.browser_version_major' => $this->clientService->getBrowserVersionMajor(),
            'client.browser_version_minor' => $this->clientService->getBrowserVersionMinor(),
            'client.browser_version_patch' => $this->clientService->getBrowserVersionPatch(),
            'client.os'                    => $this->clientService->getOs(),
            'client.os_version'            => $this->clientService->getOsVersion(),
            'client.os_version_major'      => $this->clientService->getOsVersionMajor(),
            'client.os_version_minor'      => $this->clientService->getOsVersionMinor(),
            'client.os_version_patch'      => $this->clientService->getOsVersionPatch(),
            'client.engine'                => $this->clientService->getEngine(),
            'client.languages'             => $this->clientService->getLanguages(),
            'client.language'              => $this->clientService->getLanguage(),
            'client.fingerprint'           => $this->clientService->getFingerprint(),

            // Context
            'context.provider'             => $this->contextService->getProvider(),
            'context.collection'           => $this->contextService->getCollection(),
            'context.endpoint'             => $this->contextService->getEndpoint(),

            // Request
            'request.method'               => $this->requestService->getMethod(),
            'request.scheme'               => $this->requestService->getScheme(),
            'request.is_secure'            => $this->requestService->isSecure(),
            'request.base'                 => $this->requestService->getBase(),
            'request.port'                 => $this->requestService->getPort(),
            'request.uri'                  => $this->requestService->getUri(),
            'request.path'                 => $this->requestService->getPath(),
            'request.params'               => $this->requestService->getQueryParams(),
            'request.locale'               => $this->requestService->getLocale(),

            // Server
            'server.ip'                    => $this->serverService->getIp(),
            'server.host'                  => $this->serverService->getHostname(),
            'server.env'                   => $this->serverService->getEnvironment(),
            'server.php_version'           => $this->serverService->getPhpVersion(),
            'server.symfony_version'       => $this->serverService->getSymfonyVersion(),
            'server.name'                  => $this->serverService->getName(),
            'server.software'              => $this->serverService->getSoftware(),
            'server.software_name'         => $this->serverService->getSoftwareName(),
            'server.software_version'      => $this->serverService->getSoftwareVersion(),
            'server.software_release'      => $this->serverService->getSoftwareRelease(),
            'server.os'                    => $this->serverService->getOs(),
            'server.os_version'            => $this->serverService->getOsVersion(),
            'server.os_release'            => $this->serverService->getOsRelease(),
            'server.date'                  => $this->serverService->getDate(),
            'server.time'                  => $this->serverService->getTime(),
            'server.timezone'              => $this->serverService->getTimezone(),
            'server.region'                => $this->serverService->getRegion(),

            // Status
            'status.code'                  => $response->getStatusCode(),
            'status.text'                  => Response::$statusTexts[$response->getStatusCode()] ?? '',
            'status.state'                 => $state,
            'status.is_success'            => $state === 'success',
            'status.is_failed'             => $state === 'failed',
            'status.is_error'              => $state === 'error',

            // Version
            'version.name'                 => $this->versionService->getLabel(),
            'version.label'                => $this->versionService->getLabel(),
            'version.number'               => $this->versionService->getNumber(),
            'version.prefix'               => $this->versionService->getPrefix(),
            'version.all'                  => $this->versionService->getAllVersions(),
            'version.supported'            => $this->versionService->getSupportedVersions(),
            'version.deprecated'           => $this->versionService->getDeprecatedVersions(),
            'version.is_deprecated'        => $this->versionService->isDeprecated(),
            'version.is_beta'              => $this->versionService->isBeta(),

            // Pagination
            'pagination.pages'           => $hasPagination ? $this->paginationService->getTotalPages() : false,
            'pagination.page'            => $hasPagination ? $this->paginationService->getPage() : false,
            'pagination.total'           => $hasPagination ? $this->paginationService->getTotal() : false,
            'pagination.limit'           => $hasPagination ? $this->paginationService->getLimit() : false,
            'pagination.offset'          => $hasPagination ? $this->paginationService->getOffset() : false,
            'pagination.prev'            => $hasPagination ? $this->paginationService->getPrevious() : false,
            'pagination.next'            => $hasPagination ? $this->paginationService->getNext() : false,
            'pagination.self'            => $hasPagination ? $this->paginationService->getSelf() : false,
            'pagination.first'           => $hasPagination ? $this->paginationService->getFirst() : false,
            'pagination.last'            => $hasPagination ? $this->paginationService->getLast() : false,
            'pagination.is_first'        => $hasPagination ? $this->paginationService->isFirstPage() : false,
            'pagination.is_last'         => $hasPagination ? $this->paginationService->isLastPage() : false,
            'pagination.has_prev'        => $hasPagination ? $this->paginationService->hasPreviousPage() : false,
            'pagination.has_next'        => $hasPagination ? $this->paginationService->hasNextPage() : false,

            // Security
            'user.is_authenticated'      => $this->securityService->isAuthenticated(),
            'user.id'                    => $this->securityService->getId(),
            'user.username'              => $this->securityService->getUserName(),
            'user.roles'                 => $this->securityService->getRoles(),
            'user.email'                 => $this->securityService->getEmail(),
            'user.permissions'           => $this->securityService->getPermissions(),
            'user.mfa_enabled'           => $this->securityService->isMfaEnabled(),

            // Rate Limit
            'rate_limit.limit'           => $this->rateLimitService->getLimit($provider),
            'rate_limit.remaining'       => $this->rateLimitService->getRemaining($provider),
            'rate_limit.reset'           => $this->rateLimitService->getReset($provider),

            // Debug
            'debug.memory'               => $this->debugService->getMemoryUsage(),
            'debug.peak_memory'          => $this->debugService->getMemoryPeak(),
            'debug.execution_time'       => $this->timerService->getDuration(),
            'debug.execution_time_unit'  => $this->timerService->getUnit(),
            'debug.log_level'            => $this->debugService->getLogLevel(),
            'debug.count_included_files' => $this->debugService->getCountIncludedFiles(),
            'debug.included_files'       => $this->debugService->getIncludedFiles(),

            // Response
            'response.timestamp'          => gmdate('c'),
            'response.data'               => $data,
            'response.count'              => $this->responseService->getCount(),
            'response.size'               => $this->responseService->getSize(),
            'response.algorithm'          => $this->integrityService->getAlgorithm(),
            'response.hash'               => $this->integrityService->getHash($algorithm),
            'response.hash_md5'           => $this->integrityService->getHash('md5'),
            'response.hash_sha1'          => $this->integrityService->getHash('sha1'),
            'response.hash_sha256'        => $this->integrityService->getHash('sha256'),
            'response.hash_sha512'        => $this->integrityService->getHash('sha512'),


            // 'response.is_compressed'      => $this->responseService->isCompressed(),
            // 'response.compression_format' => $this->responseService->getCompressionFormat(),
            // 'response.compression_level'  => $this->responseService->getCompressionLevel(),
            // 'response.etag'                => null,
            // 'response.validated'           => null,
            // 'response.signature'           => null,
            // 'response.cache_key'           => null,
            // 'response.cors'                => null,
            // 'response.error.code'          => null,
            // 'response.error.message'       => null,
            // 'response.error.details'       => null,
            // 'response.error.doc_url'       => null,

            // 'meta.description'      => $this->configuration->getMetadata(
            //     provider  : $provider,
            //     collection: $collection,
            //     endpoint  : $endpoint,
            //     key       : 'description'
            // ) ?? $default,
            // 'meta.license'          => null,
            // 'meta.author'           => null,
            // 'meta.contact'          => null,
            // 'meta.category'         => null,
            // 'meta.visibility'       => null,
            // 'meta.links'            => null,
            // 'meta.example_request'  => null,
            // 'meta.example_response' => null,
        ];

        // Render the template content
        $content = $this->templateService->render($type, $options, false);

        // Set the formatted content
        $response->setContent($content);
    }
}