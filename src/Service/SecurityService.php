<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ConfigurationService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class SecurityService
{
    public function __construct(
        private readonly ?AuthorizationCheckerInterface $auth,
        private readonly ConfigurationService $configuration,
        private readonly Security $security,
    ) {}

    /**
     * Check if access is granted based on roles defined in configuration
     * 
     * @return bool
     */
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

    /**
     * Get the current user
     * 
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface
    {
        return $this->security->getUser();
    }

    /**
     * Get the current user id
     * 
     * @return int|string|null
     */
    public function getId(): int|string|null
    {
        return $this->security->getUser()?->{'getId'}();
    }

    /**
     * Get the current user name
     * 
     * @return string|null
     */
    public function getUserName(): ?string
    {
        return $this->security->getUser()?->{'getUserIdentifier'}();
    }

    /**
     * Get the current user roles
     * 
     * @return string|null
     */
    public function getRoles(): ?string
    {
        return $this->security->getUser()?->{'getRoles'}();
    }

    /**
     * Get the current user email
     * 
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->security->getUser()?->{'getEmail'}();
    }

    /**
     * Get the current user token
     * 
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->security->getUser()?->{'getToken'}();
    }

    /**
     * Get the current user token issued at
     * 
     * @return \DateTimeInterface|null
     */
    public function getTokenIssuedAt(): ?\DateTimeInterface
    {
        return $this->security->getUser()?->{'getTokenIssuedAt'}();
    }

    /**
     * Get the current user token expires at
     * 
     * @return \DateTimeInterface|null
     */
    public function getTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->security->getUser()?->{'getTokenExpiresAt'}();
    }

    /**
     * Get the current user token scopes
     * 
     * @return array|null
     */
    public function getTokenScopes(): ?array
    {
        return $this->security->getUser()?->{'getTokenScopes'}();
    }

    /**
     * Get the current user permissions
     * 
     * @return array|null
     */
    public function getPermissions(): ?array
    {
        return $this->security->getUser()?->{'getPermissions'}();
    }

    /**
     * Check if the current user has multi-factor authentication enabled
     * 
     * @return bool|null
     */
    public function getMfaEnabled(): ?bool
    {
        return $this->security->getUser()?->{'isMfaEnabled'}();
    }
}