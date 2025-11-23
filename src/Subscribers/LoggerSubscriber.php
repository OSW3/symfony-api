<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\DebugService;
use OSW3\Api\Service\RouteService;
use OSW3\Api\Service\IntegrityService;
use OSW3\Api\Service\ConfigurationService;
use OSW3\Api\Service\ExecutionTimeService;
use OSW3\Api\Enum\Logger\ExecutionTimeUnit;
use OSW3\Api\Service\PaginationService;
use OSW3\Api\Service\SecurityService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles logging of execution time during the request lifecycle.
 * 
 * @stage 0
 * @priority 0
 * @before ResponseSubscriber
 * @after -
 */
class LoggerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ExecutionTimeService $timer,
        private readonly ConfigurationService $configuration,
        // private readonly ContextService $contextService,
        // private readonly AuthenticationService $authentication,
        private readonly SecurityService $securityService,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            // High priority to log the start early
            // KernelEvents::REQUEST => ['onRequest', 1000],

            // Low priority to log the end late, just before ResponseSubscriber
            // KernelEvents::RESPONSE => ['onResponse', -9], 
            // KernelEvents::RESPONSE => ['debug', 0], 

            // Lowest priority to log the end after all other subscribers
            // KernelEvents::TERMINATE => ['onTerminate'],
        ];
    }

    public function debug(): void
    {
        $segment    = $this->configuration->getContext('segment');
        // $segment    = ContextService::SEGMENT_COLLECTION;
        // $segment    = ContextService::SEGMENT_AUTHENTICATION;
        $provider   = $this->configuration->getContext('provider');
        $collection = $this->configuration->getContext('collection');
        $endpoint   = $this->configuration->getContext('endpoint');


        dump([
            'provider'   => $provider,
            'segment'    => $segment,
            'collection' => $collection,
            'endpoint'   => $endpoint,
        ]);
        dd([
            $this->securityService->getUser(),
            $this->securityService->isAuthenticated(),
            $this->securityService->getId(),
            $this->securityService->getIdentifier(),
            $this->securityService->getUsername(),
            $this->securityService->getRoles(),
            $this->securityService->getEmail(),
            $this->securityService->getPermissions(),
            $this->securityService->isMfaEnabled(),
        ]);

        // $collections = $this->configuration->getCollections($provider, 'collections');
        // $authentications = $this->configuration->getCollections($provider, 'authentication');
        dd([
            // 'section'    => $section,
            // 'provider'   => $provider,
            // 'collection' => $collection,
            // 'endpoint'   => $endpoint,
            // 'collections' => $collections,
            // 'authentications' => $authentications,
        ]);
    }

    // public function onRequest(): void
    // {
    //     // Start the execution timer
    //     $this->timer->start();
    // }

    // public function onResponse(): void
    // {
    //     // Stop the execution timer
    //     $this->timer->stop();
    // }

    public function onTerminate(): void
    {
        // Log the total execution time
        $duration = $this->timer->getDuration();
        $unit = $this->timer->getUnit();
        // Here you would typically log this information to a file or monitoring system
        dump("Total Execution Time: {$duration} {$unit}");
    }
}