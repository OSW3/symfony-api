<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class SecurityService
{
    public function __construct(
        private readonly ?AuthorizationCheckerInterface $auth,
        private readonly ConfigurationService $configuration,
        
    ) {}

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