<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ConfigurationService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class SecurityService
{
    public function __construct(
        private readonly ?AuthorizationCheckerInterface $auth,
        private readonly ConfigurationService $configuration,
        private readonly Security $security,
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

    public function getUser()
    {
        return $this->security->getUser();
    }

    public function getId(): int|string|null
    {
        return $this->security->getUser()?->{'getId'}();
    }

    public function getUserName(): ?string
    {
        return $this->security->getUser()?->{'getUserIdentifier'}();
    }

    public function getRoles(): ?string
    {
        return $this->security->getUser()?->{'getRoles'}();
    }

    public function getEmail(): ?string
    {
        return $this->security->getUser()?->{'getEmail'}();
    }
}