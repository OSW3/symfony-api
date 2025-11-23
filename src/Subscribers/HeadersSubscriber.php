<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\ServerService;
use OSW3\Api\Service\HeadersService;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class HeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        // private readonly AppService $appService,
        private readonly HeadersService $headersService,
        private readonly ServerService $serverService,
    ){}
    
    public static function getSubscribedEvents(): array
    {
        return [
            // KernelEvents::RESPONSE => ['onResponse', -100],
            // KernelEvents::RESPONSE => ['onResponse', 0],
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        // dump('FINAL - HeadersSubscriber::onResponse');
        
        if (!$event->isMainRequest()) {
            return;
        }

        // Get current response
        $response = $event->getResponse();
        $exposed  = $this->headersService->getExposedDirectives();
        $custom   = $this->headersService->getCustomDirectives();
        $removed  = $this->headersService->getDirectivesList('remove');


        // Keep exposed headers
        // foreach ($exposed as $key => $value) 
        // {
        //     $key = $this->headersService->toHeaderCase($key);

        //     if ($value === false) {
        //         $response->headers->remove($key);
        //     }
        //     elseif ($value === true) {
        //         $xStrippedKey = strtolower(preg_replace('/^X-/i', '', $key));

        //         $value = match($xStrippedKey) {

        //             'server' => $this->serverService->getSoftware(),
        //             // 'content-length' => $this->responseService->getSize(),

        //             // App
        //             'app-name'           => $this->appService->getName(),
        //             'app-vendor'         => $this->appService->getVendor(),
        //             'app-version'        => $this->appService->getVersion(),
        //             'app-description'    => $this->appService->getDescription(),
        //             'app-license'        => $this->appService->getLicense(),
                    
        //             // API Version
        //             // 'api-version'             => $this->headersService->toHeaderValue($this->versionService->getLabel()),
        //             // 'api-all-versions'        => $this->headersService->toHeaderValue($this->versionService->getAllVersions()),
        //             // 'api-supported-versions'  => $this->headersService->toHeaderValue($this->versionService->getSupportedVersions()),
        //             // 'api-deprecated-versions' => $this->headersService->toHeaderValue($this->versionService->getDeprecatedVersions()),

        //             default         => $value,
        //         };

        //         $response->headers->set($key, $value);
        //     }
        // }

        // Add custom headers
        foreach ($custom as $key => $value) 
        {
            $key = $this->headersService->toHeaderCase($key);
            $response->headers->set($key, $value);
        }

        // Remove headers
        foreach ($removed as $key) 
        {
            $key = $this->headersService->toHeaderCase($key);
            $response->headers->remove($key);
        }

        // Strip X Prefix
        foreach ($response->headers->all() as $key => $value) {
            $newKey = $key;

            if ($this->headersService->stripXPrefix() &&  str_starts_with(strtolower($key), 'x-')) 
            {
                
                $newKey = substr($key, 2);
                $response->headers->remove($key);
                $response->headers->set($newKey, $value);
            }

            if ($this->headersService->keepLegacy() && $key !== $newKey) {
                $response->headers->set($key, $value);
            }
        }

        $h = $response->headers->all();
        // unset($h['cache-control']);
        // unset($h['date']);

        // unset($h['content-type']);
        dump($response->getStatusCode());
        // dump($h);
        dd($response->getContent());
    }
}