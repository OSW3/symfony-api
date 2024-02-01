<?php 
namespace OSW3\Api\Subscribers;

use OSW3\Api\Services\ConfigurationService;
use OSW3\Api\Services\RequestService;
use OSW3\Api\Services\ResponseService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SearchSubscriber  implements EventSubscriberInterface 
{
    public function __construct(
        private RequestService $requestService,
        private ConfigurationService $configurationService,

        private RequestStack $requestStack,
    ){}
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    private function supports(): bool
    {
        return $this->requestStack->getCurrentRequest()->get('_route') === 'api:search';
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$this->supports())
        {
            return;
        }

        $search = [];

        // Get all providers
        $providers = $this->configurationService->getProviders();

        foreach ($providers as $providerName => $provider)
        {
            // Get all collections of each providers
            $collections = $this->configurationService->getCollections($providerName);

            // Get all entity class
            foreach ($collections as $collectionName => $collection)
            {
                // $collection = $this->configurationService->getCollection($providerName, $collectionName);
                $class = $this->configurationService->getClass($providerName, $collectionName);

                dump($provider['search']);
                dump($class);
            }
        }

        // Search URL param
        // $search_param = $this->providerData['search']['param'];
        // $expression = $this->request->get($search_param);
        dd('');
    }
    
    public function onResponse(ResponseEvent $event): void
    {
        if (!$this->supports())
        {
            return;
        }
    }
}