<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\AppService;
use OSW3\Api\Service\DebugService;
use OSW3\Api\Service\ClientService;
use OSW3\Api\Service\ServerService;
use OSW3\Api\Builder\OptionsBuilder;
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
        private readonly OptionsBuilder $optionsBuilder,
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

        // // Determine the state from the status code
        // $state = match (true) {
        //     $response->getStatusCode() >= 200 && $response->getStatusCode() < 300 => 'success',
        //     $response->getStatusCode() >= 400 && $response->getStatusCode() < 500 => 'failed',
        //     default                    => 'error',
        // };

        // Get the integrity algorithm
        $algorithm = $this->integrityService->getAlgorithm();

        // Check if pagination is enabled
        // $hasPagination = $this->paginationService->isEnabled();

        // Build the template options
        // $this->optionsBuilder->setContext('template');
        // $options = [
        //  

        //     // Debug
        //     'debug.memory'               => $this->debugService->getMemoryUsage(),
        //     'debug.peak_memory'          => $this->debugService->getMemoryPeak(),
        //     'debug.execution_time'       => $this->timerService->getDuration(),
        //     'debug.execution_time_unit'  => $this->timerService->getUnit(),
        //     'debug.log_level'            => $this->debugService->getLogLevel(),
        //     'debug.count_included_files' => $this->debugService->getCountIncludedFiles(),
        //     'debug.included_files'       => $this->debugService->getIncludedFiles(),

        //     // Response
        //     'response.timestamp'          => gmdate('c'),
        //     'response.data'               => $data,
        //     'response.count'              => $this->responseService->getCount(),
        //     'response.size'               => $this->responseService->getSize(),
        //     'response.algorithm'          => $this->integrityService->getAlgorithm(),
        //     'response.hash'               => $this->integrityService->getHash($algorithm),
        //     'response.hash_md5'           => $this->integrityService->getHash('md5'),
        //     'response.hash_sha1'          => $this->integrityService->getHash('sha1'),
        //     'response.hash_sha256'        => $this->integrityService->getHash('sha256'),
        //     'response.hash_sha512'        => $this->integrityService->getHash('sha512'),


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
        // ];

        // Render the template content
        // $content = $this->templateService->render($type, $options, false);
        $content = $this->templateService->render($response, $type, false);

        // Set the formatted content
        $response->setContent($content);
    }
}