<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles locale-related tasks during the request lifecycle.
 * 
 * @stage 2
 * @priority 30
 * @before RequestSubscriber
 * @after AuthenticationSubscriber
 */
class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ConfigurationService $configuration,
    ){}
    
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 30]];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (in_array($event->getRequest()->attributes->get('_api_endpoint'), ['register', 'login'], true)) {
            return;
        }


        // dump([
        //     __CLASS__, 
        //     '---',
        // ]);

        $request = $event->getRequest();

    //     // Exemple : récupère l’en-tête Accept-Language (ex: fr, en, de)
    //     // $locale = $request->headers->get('Accept-Language', 'fr');
    //     $locale = $request->headers->get('Accept-Language');
    //     // $locale = substr($locale, 0, 2); // Ne garde que 'fr' ou 'en'


    //     // $locale = $request->headers->get('X-Locale', 'fr');
    //     $timezone = $request->headers->get('X-Timezone', 'Europe/Paris');
    //     $region = $request->headers->get('X-Region');


    //     dump([
    //         'locale'   => $locale,
    //         'timezone' => $timezone,
    //         'region'   => $region,
    //     ]);
    //     dd($event->getRequest());
    }
}