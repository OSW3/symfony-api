<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class SecurityService
{
    private readonly Request $request;
    
    public function __construct(
        private readonly ?AuthorizationCheckerInterface $auth,
        private readonly ConfigurationService $configuration,
        private readonly RequestStack $requestStack,
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function accessGranted(): bool
    {
        if (null === $this->auth) {
            return true;
        }

        $provider   = $this->configuration->guessProvider();
        $collection = $this->configuration->guessCollection();
        $endpoint   = $this->configuration->guessEndpoint();
        $roles      = $this->configuration->getRoles($provider, $collection, $endpoint);

        foreach ($roles as $role) {
            if ($this->auth->isGranted($role)) {
                return true;
            }
        }
        
        return false;
    }

}