<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class AuthenticationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ?TranslatorInterface $translator,
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
        private readonly AuthorizationCheckerInterface $auth,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            // KernelEvents::REQUEST => ['onRequest', 31]
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        // dump('2 - AuthenticationSubscriber');

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

        $granted = false;

        if (!$event->isMainRequest()) {
            return;
        }

        if (in_array($endpoint, $securityEndpoints, true)) {
            return;
        }

        $roles = $this->configurationService->getAccessControlRoles(
            $provider,
            $collection,
            $endpoint
        );
        
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