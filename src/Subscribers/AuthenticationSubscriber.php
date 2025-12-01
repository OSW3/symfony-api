<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\AccessControlService;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpKernel\KernelEvents;
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
        private readonly AccessControlService $accessControlService,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 31]
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        // Is main request
        if (!$event->isMainRequest()) {
            return;
        }

        // $provider   = $this->contextService->getProvider();
        $segment    = $this->contextService->getSegment();
        // $collection = $this->contextService->getCollection();
        // $endpoint   = $this->contextService->getEndpoint();
        $isGranted  = false;

        // Skip if not in collection segment
        if ($segment !== ContextService::SEGMENT_COLLECTION) {
            return;
        }


        // Get allowed roles for the current context
        $roles = $this->accessControlService->getContextAllowedRoles();

        if (empty($roles)) {
            return;
        }

        foreach ($roles as $role) {
            if ($isGranted = $this->auth->isGranted($role)) {
                break;
            }
        }

        if (!$isGranted) {
            $message = $this->translator?->trans("error.access_denied", [
                '%roles%' => implode(', ', $roles),
            ], 'messages') ?? 'Access Denied.';

            throw new AccessDeniedHttpException($message);
        }
    }
}