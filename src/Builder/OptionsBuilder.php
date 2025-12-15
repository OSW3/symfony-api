<?php 
namespace OSW3\Api\Builder;

use OSW3\Api\Service\AppService;
use OSW3\Api\Service\MetaService;
use OSW3\Api\Service\DebugService;
use OSW3\Api\Service\ClientService;
use OSW3\Api\Service\ServerService;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\VersionService;
use OSW3\Api\Service\ResponseService;
use OSW3\Api\Service\SecurityService;
use OSW3\Api\Service\IntegrityService;
use OSW3\Api\Service\RateLimitService;
use OSW3\Api\Service\PaginationService;
use OSW3\Api\Service\CompressionService;
use OSW3\Api\Service\ExecutionTimeService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

final class OptionsBuilder 
{
    private ?string $context = null;

    public function __construct(
        private readonly AppService $appService,
        private readonly MetaService $metaService,
        private readonly DebugService $debugService,
        private readonly ClientService $clientService,
        private readonly ServerService $serverService,
        private readonly ContextService $contextService,
        private readonly VersionService $versionService,
        private readonly RequestService $requestService,
        private readonly ResponseService $responseService,
        private readonly SecurityService $securityService,
        private readonly ExecutionTimeService $timerService,
        private readonly IntegrityService $integrityService,
        private readonly RateLimitService $rateLimitService,
        private readonly PaginationService $paginationService,
        private readonly CompressionService $compressionService,
    ) {}

    public function setContext(string $context): void 
    {
        $this->context = $context;
    }

    public function getContext(): ?string 
    {
        return $this->context;
    }

    private function inContext(array $allowed = ['headers', 'template']): bool 
    {
        return in_array($this->context, $allowed);
    }

    public function build(string $key, $value, array $options = []): mixed 
    {
        $key = preg_replace('/^x-/', '', strtolower($key));
        $key = preg_replace('/-/', '.', strtolower($key));
        $key = preg_replace('/_/', '.', strtolower($key));


        // e.g. Template key => 'app.name', 'app.version', 'app.description', etc.
        // e.g. Headers key => 'X-App-Name', 'X-App-Version', 'X-App-Description', etc.

        $value =match (strtok($key, '.')) {

            'response' => (function() use ($key, $options): mixed {
                
                $content = $options['response']->getContent();

                // Get the template data
                $data = json_decode($content, true);
                
                // Get the integrity algorithm
                $algorithm = $this->integrityService->getAlgorithm();

                return match ($key) {
                    'response.timestamp'          => gmdate('c'),
                    'response.data'               => $data,
                    'response.count'              => $this->responseService->getCount(),
                    'response.size'               => $this->responseService->getSize(),
                    'response.algorithm'          => $this->integrityService->getAlgorithm(),
                    'response.hash'               => $this->integrityService->getHash($algorithm),
                    'response.hash.md5'           => $this->integrityService->getHash('md5'),
                    'response.hash.sha1'          => $this->integrityService->getHash('sha1'),
                    'response.hash.sha256'        => $this->integrityService->getHash('sha256'),
                    'response.hash.sha512'        => $this->integrityService->getHash('sha512'),
                    'response.is.compressed'      => $this->compressionService->isEnabled(),
                    'response.compression.format' => $this->compressionService->getFormat(),
                    'response.compression.level'  => $this->compressionService->getLevel(),

                    // 'response.etag'                => null,
                    // 'response.validated'           => null,
                    // 'response.signature'           => null,
                    // 'response.cache_key'           => null,
                    // 'response.cors'                => null,
                    // 'response.error.code'          => null,
                    // 'response.error.message'       => null,
                    // 'response.error.details'       => null,
                    // 'response.error.doc_url'       => null,

                    default           => null
                };
            })(),

            'app' => match ($key) {
                'app.name'        => $this->appService->getName() ?? null,
                'app.vendor'      => $this->appService->getVendor() ?? null,
                'app.version'     => $this->appService->getVersion() ?? null,
                'app.description' => $this->appService->getDescription() ?? null,
                'app.license'     => $this->appService->getLicense() ?? null,
                default           => null
            },

            'context' => match ($key) {
                'context.provider'   => $this->contextService->getProvider() ?? null,
                'context.segment'    => $this->contextService->getSegment() ?? null,
                'context.collection' => $this->contextService->getCollection() ?? null,
                'context.endpoint'   => $this->contextService->getEndpoint() ?? null,
                default              => null
            },

            'version' => match ($key) {
                'version.label'  => $this->versionService->getLabel() ?? null,
                'version.number' => $this->versionService->getNumber(),
                'version.prefix' => $this->versionService->getPrefix() ?? null,
                'version.all'    => (function(): array|string { 
                    $versions = $this->versionService->getAllVersions();

                    return $this->inContext(['headers']) 
                        ? json_encode($versions) 
                        :  $versions;
                })(),
                'version.supported' => (function(): array|string { 
                    $versions = $this->versionService->getSupportedVersions();

                    return $this->inContext(['headers'])
                        ? json_encode($versions) 
                        :  $versions;
                })(),
                'version.deprecated' => (function(): array|string { 
                    $versions = $this->versionService->getDeprecatedVersions();

                    return $this->inContext(['headers'])
                        ? json_encode($versions) 
                        :  $versions;
                })(),
                'version.is.deprecated' => (function(): string|bool { 
                    $isDeprecated = $this->versionService->isDeprecated();

                    if ($this->inContext(['headers'])) {
                        return $isDeprecated ? 'true': 'false';
                    }
                        
                    return $isDeprecated;
                })(),
                'version.is.beta' => (function(): string|bool { 
                    $isBeta = $this->versionService->isBeta();

                    if ($this->inContext(['headers'])) {
                        return $isBeta ? 'true': 'false';
                    }
                        
                    return $isBeta;
                })(),
                default => null,
            },

            'status' => (function() use ($key, $options): bool|int|string|null {

                // Determine the state from the status code
                $state = match (true) {
                    $options['response']->getStatusCode() >= 200 && $options['response']->getStatusCode() < 300 => 'success',
                    $options['response']->getStatusCode() >= 400 && $options['response']->getStatusCode() < 500 => 'failed',
                    default => 'error',
                };

                return match ($key) {
                    'status.code'  => $options['response']->getStatusCode(),
                    'status.text'  => Response::$statusTexts[$options['response']->getStatusCode()] ?? '',
                    'status.state' => $state,
                    'status.is.success' => (function() use ($state): bool|string {
                        $isSuccess = $state === 'success';

                        return $this->inContext(['headers']) 
                            ? self::boolToString($isSuccess)
                            : $isSuccess;
                    })(),
                    'status.is.failed' => (function() use ($state): bool|string {
                        $isFailed = $state === 'failed';

                        return $this->inContext(['headers']) 
                            ? self::boolToString($isFailed)
                            : $isFailed;
                    })(),
                    'status.is.error' => (function() use ($state): bool|string {
                        $isError = $state === 'error';

                        return $this->inContext(['headers']) 
                            ? self::boolToString($isError)
                            : $isError;
                    })(),
                    default => null
                };
            })(),

            'request' => match ($key) {
                'request.method'    => $this->requestService->getMethod(),
                'request.scheme'    => $this->requestService->getScheme(),
                'request.is.secure' => (function(): bool|string {
                    $isSecure = $this->requestService->isSecure();

                    return $this->inContext(['headers']) 
                        ? self::boolToString($isSecure)
                        :  $isSecure;
                })(),
                'request.base'   => $this->requestService->getBaseUrl(),
                'request.port'   => $this->requestService->getPort(),
                'request.uri'    => $this->requestService->getUri(),
                'request.path'   => $this->requestService->getPathInfo(),
                'request.params' => (function(): array|string {
                    $parameters = $this->requestService->getQueryParameters();

                    return $this->inContext(['headers']) 
                        ? json_encode($parameters) 
                        :  $parameters;
                })(),
                'request.locale' => $this->requestService->getLocale(),
                default          => null
            },

            'client' => match ($key) {
                'client.ip' => $this->clientService->getIp(),
                'client.user.agent' => $this->clientService->getUserAgent(),
                'client.device' => $this->clientService->getDevice(),
                'client.is.mobile' => (function(): bool|string {
                    $isMobile = $this->clientService->isMobile();
                    
                    return $this->inContext(['headers']) 
                        ? self::boolToString($isMobile)
                        : $isMobile;
                })(),
                'client.is.tablet' => (function(): bool|string {
                    $isTablet = $this->clientService->isTablet();
                    
                    return $this->inContext(['headers']) 
                        ? self::boolToString($isTablet)
                        : $isTablet;
                })(),
                'client.is.desktop' => (function(): bool|string {
                    $isDesktop = $this->clientService->isDesktop();
                    
                    return $this->inContext(['headers']) 
                        ? self::boolToString($isDesktop)
                        : $isDesktop;
                })(),
                'client.browser' => $this->clientService->getBrowser(),
                'client.browser.version' => $this->clientService->getBrowserVersion(),
                'client.browser.version.major' => $this->clientService->getBrowserVersionMajor(),
                'client.browser.version.minor' => $this->clientService->getBrowserVersionMinor(),
                'client.browser.version.patch' => $this->clientService->getBrowserVersionPatch(),
                'client.os' => $this->clientService->getOs(),
                'client.os.version' => $this->clientService->getOsVersion(),
                'client.os.version.major' => $this->clientService->getOsVersionMajor(),
                'client.os.version.minor' => $this->clientService->getOsVersionMinor(),
                'client.os.version.patch' => $this->clientService->getOsVersionPatch(),
                'client.engine' => $this->clientService->getEngine(),
                'client.languages' => (function(): array|string { 
                    $languages = $this->clientService->getLanguages();

                    return $this->inContext(['headers']) 
                        ? json_encode($languages) 
                        : $languages;
                })(),
                'client.language' => $this->clientService->getLanguage() ?? 'unknown',
                'client.fingerprint' => $this->clientService->getFingerprint() ?? 'unknown',
                default => null
            },

            'server' => match ($key) {
                'server.ip'               => $this->serverService->getIp(),
                'server.host'             => $this->serverService->getHostname(),
                'server.env'              => $this->serverService->getEnvironment() ?? 'unknown',
                'server.php.version'      => $this->serverService->getPhpVersion() ?? 'unknown',
                'server.symfony.version'  => $this->serverService->getSymfonyVersion() ?? 'unknown',
                'server.name'             => $this->serverService->getName() ?? 'unknown',
                'server.software'         => $this->serverService->getSoftware() ?? 'unknown',
                'server.software.name'    => $this->serverService->getSoftwareName() ?? 'unknown',
                'server.software.version' => $this->serverService->getSoftwareVersion() ?? 'unknown',
                'server.software.release' => $this->serverService->getSoftwareRelease() ?? 'unknown',
                'server.os'               => $this->serverService->getOs() ?? 'unknown',
                'server.os.version'       => $this->serverService->getOsVersion() ?? 'unknown',
                'server.os.release'       => $this->serverService->getOsRelease() ?? 'unknown',
                'server.date'             => $this->serverService->getDate() ?? 'unknown',
                'server.time'             => $this->serverService->getTime() ?? 'unknown',
                'server.timezone'         => $this->serverService->getTimezone() ?? 'unknown',
                'server.region'           => $this->serverService->getRegion() ?? 'unknown',
                default                   => null,
            },

            'pagination' => (function() use ($key): bool|int|string|null {
                
                if (!$this->paginationService->isEnabled()) {
                    return false;
                }

                return match ($key) {
                    'pagination.pages'    => $this->paginationService->getTotalPages(),
                    'pagination.page'     => $this->paginationService->getPage(),
                    'pagination.total'    => $this->paginationService->getTotal(),
                    'pagination.limit'    => $this->paginationService->getLimit(),
                    'pagination.offset'   => $this->paginationService->getOffset(),
                    'pagination.prev'     => $this->paginationService->getPreviousUrl(),
                    'pagination.next'     => $this->paginationService->getNextUrl(),
                    'pagination.self'     => $this->paginationService->getCurrentUrl(),
                    'pagination.first'    => $this->paginationService->getFirstUrl(),
                    'pagination.last'     => $this->paginationService->getLastUrl(),
                    'pagination.is.first' => (function(): bool|string { 
                        $isFirstPage = $this->paginationService->isFirstPage();
                        
                        return $this->inContext(['headers']) 
                            ? self::boolToString($isFirstPage)
                            : $isFirstPage;
                    })(),
                    'pagination.is.last' => (function(): bool|string {
                        $isLastPage = $this->paginationService->isLastPage();
                        
                        return $this->inContext(['headers']) 
                            ? self::boolToString($isLastPage)
                            : $isLastPage;
                    })(),
                    'pagination.has.prev' => (function(): bool|string {
                        $hasPreviousPage = $this->paginationService->hasPreviousPage();
                        
                        return $this->inContext(['headers']) 
                            ? self::boolToString($hasPreviousPage)
                            : $hasPreviousPage;
                    })(),
                    'pagination.has.next' => (function(): bool|string {
                        $hasNextPage = $this->paginationService->hasNextPage();

                        return $this->inContext(['headers']) 
                            ? self::boolToString($hasNextPage)
                            : $hasNextPage;
                    })(),
                    default => null,
                };

            })(),

            'user' => match ($key) {
                'user.is.authenticated' => (function(): bool { 
                    $isAuthenticated = $this->securityService->isAuthenticated();

                    return $this->inContext(['headers']) 
                        ? self::boolToString($isAuthenticated, true)
                        : $isAuthenticated;
                })(),
                'user.id' => $this->securityService->getId() ?? 'unknown',
                'user.username' => $this->securityService->getUserName() ?? 'unknown',
                'user.roles' => (function(): array|string { 
                    $roles = $this->securityService->getRoles();

                    return $this->inContext(['headers']) 
                        ? json_encode($roles) 
                        : $roles;
                })(),
                'user.email' => $this->securityService->getEmail() ?? 'unknown',
                'user.permissions' => (function(): array|string { 
                    $permissions = $this->securityService->getPermissions();

                    return $this->inContext(['headers']) 
                        ? json_encode($permissions) 
                        : $permissions;
                })(),
                'user.mfa.enabled' => (function(): bool|string {
                    $isMfaEnabled = $this->securityService->isMfaEnabled();

                    return $this->inContext(['headers']) 
                        ? self::boolToString($isMfaEnabled, true)
                        : $isMfaEnabled;
                })(),
                default => null,
            },

            'rate' ,'ratelimit' => (function() use ($key): int|null {

                $provider = $this->contextService->getProvider();

                return match ($key) {
                    'rate.limit.limit',
                    'ratelimit.limit'       => $this->rateLimitService->getLimit($provider)['requests'],
                    'rate.limit.period',
                    'ratelimit.period'      => $this->rateLimitService->getLimit($provider)['period'],
                    'rate.limit.remaining',
                    'ratelimit.remaining'   => $this->rateLimitService->getRemaining($provider) ?: -1,
                    'rate.limit.reset',
                    'ratelimit.reset'       => $this->rateLimitService->getReset($provider) ?: -1,
                    default                 => null,
                };

            })(),

            'debug' => (function() use ($key): int|float|string|bool|array|null {

                return match ($key) {
                    'debug.memory'               => $this->debugService->getMemoryUsage(),
                    'debug.peak.memory'          => $this->debugService->getMemoryPeak(),
                    'debug.execution.time'       => $this->timerService->getDuration(),
                    'debug.execution.time.unit'  => $this->timerService->getUnit(),
                    'debug.log.level'            => $this->debugService->getLogLevel(),
                    'debug.count.included.files' => $this->debugService->getCountIncludedFiles(),
                    'debug.included.files'       => $this->inContext(['template']) ? $this->debugService->getIncludedFiles(): false,
                    default                      => null,
                };

            })(),

            'meta' => (function() use ($key): mixed {
                $part  = str_replace('meta.', '', $key);
                $value = $this->metaService->getAll()[$part] ?? null;

                if (is_null($value)) {
                    return 'null';
                } 
                else if (is_array($value)) {
                    return $this->inContext(['headers']) 
                        ? json_encode($value) 
                        : $value;
                }
                else if (is_callable($value)) {
                    return $value();
                }

                return $value;
            })(),

            default => null,
        };

        return $value;
    }



    public static function boolToString(bool $value, bool $yesNo = false): string 
    {
        return $value 
            ? ($yesNo ? 'yes' : 'true')
            : ($yesNo ? 'no' : 'false')
        ;
    }





    private function computeVary(ResponseEvent $event, mixed $data): ?string 
    {
        $response = $event->getResponse();
        
        $store = $response->headers->all('vary');
        $response->headers->remove('vary');

        if ($data === false || $data === null) {
            return null;
        }

        if (!is_array($data)) {
            $data = [$data];
        }
        
        return implode(', ', array_values(array_unique(array_merge(
            $store,
            $data
        ))));
    }
}