<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\ConfigurationService;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\RouteService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Handles authentication-related tasks during the request lifecycle.
 * 
 * @stage 1
 * @priority 32
 * @before LocaleSubscriber
 * @after -
 */
final class AuthenticationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RouteService $routeService,
        private readonly ?TranslatorInterface $translator,
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
        private readonly AuthorizationCheckerInterface $auth,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 32]
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();
        $security   = $this->configurationService->getSecurity($provider);
        $routeName  = $event->getRequest()->attributes->get('_route');
        
        $securityEndpoints = array_keys(array_merge(
            $security['registration'] ?? [], 
            $security['authentication'] ?? [], 
            $security['password'] ?? [], 
        ));

        $roles = $this->configurationService->getAccessControlRoles(
            $provider,
            $collection,
            $endpoint
        );

        $granted = false;

        if (!$event->isMainRequest()) {
            return;
        }

        // $context = $this->routeService->getContext();


        if (in_array($endpoint, $securityEndpoints, true)) {
            return;
        }

        if (empty($roles)) {
            return;
        }
        
        foreach ($roles as $role) {
            if ($this->auth->isGranted($role)) {
                $granted = true;
                break;
            }
        }

        if (!$granted) {
            $message = $this->translator?->trans("error.access_denied", [
                '%roles%' => implode(', ', $roles),
            ], 'messages') ?? 'Access Denied.';

            throw new AccessDeniedHttpException($message);
        }
    }
}