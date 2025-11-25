<?php 
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\CorsService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


final class CorsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CorsService $corsService,
    ){}
    
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', -900],
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }


        if (!$this->corsService->isEnabled()) 
        {
            return;
        }

        // Get current response
        $response  = $event->getResponse();
    
        
        // Set Origin headers
        $origins = $this->corsService->getOrigins();
        $response->headers->set(
            'Access-Control-Allow-Origin', 
            count($origins) === 1 ? $origins[0] : implode(', ', $origins)
        );

        // Set Methods headers
        $response->headers->set(
            'Access-Control-Allow-Methods', 
            implode(', ', $this->corsService->getMethods())
        );

        // Set Headers attributes
        $headers = $this->corsService->getHeaders();
        if (!empty($headers)) {
            $response->headers->set(
                'Access-Control-Allow-Headers',
                implode(', ', $headers)
            );
        }

        // Expose
        $exposed = $this->corsService->getExposedHeaders();
        if (!empty($exposed)) {
            $response->headers->set(
                'Access-Control-Expose-Headers',
                implode(', ', $exposed)
            );
        }


        // Set Credentials attribute
        if ($this->corsService->exposeCredentials()) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        // Set Max-Age attribute
        $maxAge = $this->corsService->getMaxAge();
        if ($maxAge !== null) {
            $response->headers->set('Access-Control-Max-Age', $maxAge);
        }

    }
}