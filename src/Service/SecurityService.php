<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ConfigurationService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class SecurityService
{
    private ?UserInterface $cachedUser = null;

    public function __construct(
        // private readonly ?AuthorizationCheckerInterface $auth,
        private readonly ConfigurationService $configuration,
        private readonly Security $security,
    ) {}

    /**
     * Get the current user
     * 
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface
    {
        return $this->cachedUser ??= $this->security->getUser();
    }

    /**
     * Check if the current user is authenticated
     * 
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return null !== $this->getUser();
    }

    /**
     * Get the current user id
     * 
     * @return int|string|null
     */
    public function getId(): int|string|null
    {
        $user = $this->getUser();
        return $user?->{'getId'}();
    }

    /**
     * Get the current user name
     * 
     * @return string|null
     */
    public function getUserName(): ?string
    {
        return $this->getUser()?->{'getUserIdentifier'}();
    }

    /**
     * Get the current user roles
     * 
     * @return string|null
     */
    public function getRoles(): array
    {
        return $this->getUser()?->{'getRoles'}() ?? [];
    }

    /**
     * Check if the current user has a specific role
     * 
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles() ?? [], true);
    }

    /**
     * Get the current user email
     * 
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->getUser()?->{'getEmail'}();
    }

    /**
     * Get the current user permissions
     * 
     * @return array|null
     */
    public function getPermissions(): ?array
    {
        return []; // $this->getUser()?->{'getPermissions'}();
    }

    /**
     * Check if the current user has multi-factor authentication enabled
     * 
     * @return bool|null
     */
    public function getMfaEnabled(): ?bool
    {
        return false; //$this->getUser()?->{'isMfaEnabled'}();
    }
}