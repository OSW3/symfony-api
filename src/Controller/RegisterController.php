<?php
namespace OSW3\Api\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OSW3\Api\Service\ConfigurationService;
use OSW3\Api\Service\RouteService;
use OSW3\Api\Service\VersionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterController
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ConfigurationService $configuration,
        private readonly RouteService $routeService,
        private readonly VersionService $versionService,
    ){}


    /**
     * Find the provider name based on the current route and action.
     *
     * @param array $providers
     * @param string $route
     * @param string $action
     * @return string|null
     */
    private function findMatchingProvider(array $providers, string $route, string $action): ?string
    {
        foreach ($providers as $providerName => $provider) {
            if ($this->routeService->getRouteNameByProvider($providerName, $action) === $route) {
                return $providerName;
            }
        }

        return null;
    }

    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse 
    {
        // --- 1. Request context
        $route  = $this->requestStack->getCurrentRequest()->attributes->get('_route');
        $action = $this->requestStack->getCurrentRequest()->attributes->get('_api_endpoint');
        $data   = json_decode($request->getContent(), true) ?? [];


        // --- 2. Retrieve the provider name from the route
        $providers = $this->configuration->getAllProviders();
        $providerName = $this->findMatchingProvider($providers, $route, $action);

        if ($providerName === null) {
            return new JsonResponse(['error' => 'Unknown provider or route'], 400);
        }


        // --- 3. Check if registration is enabled
        if (!$this->configuration->isSecurityRegistrationEnabled($providerName)) {
            return new JsonResponse(['error' => 'Registration is disabled'], 403);
        }

        
        // --- 4. Retrieve the provider configuration
        $entityClass = $this->configuration->getSecurityEntityClass($providerName);
        $properties  = $this->configuration->getSecurityRegistrationProperties($providerName);

        if (empty($entityClass)) {
            return new JsonResponse(['error' => 'No security entity defined'], 500);
        }


        // --- 5. Check required fields
        $propertyUsername = $properties['username'] ?? 'email';
        $propertyPassword = $properties['password'] ?? 'password';

        if (empty($data[$propertyUsername]) || empty($data[$propertyPassword])) {
            return new JsonResponse(['error' => 'Username and password are required'], 400);
        }

        $username = $data[$propertyUsername];
        // $password = $data[$propertyPassword];


        // --- 6. Check user existence
        $existingUser = $em->getRepository($entityClass)->findOneBy([
            $propertyUsername => $username,
        ]);

        if ($existingUser) {
            return new JsonResponse(['error' => 'User already exists'], 400);
        }


        // --- 7. Create the new user
        $user = new $entityClass();
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($properties as $key => $property) {
            if (!array_key_exists($property, $data)) {
                continue;
            }

            $value = ($property === $propertyPassword)
                ? $passwordHasher->hashPassword($user, $data[$property])
                : $data[$property];

            $accessor->setValue($user, $property, $value);
        }


        // --- 8. Database persistence
        $em->persist($user);
        $em->flush();


        // --- 9. Response
        return new JsonResponse([
            'message' => 'User created successfully',
        ], 201);
    }
}
