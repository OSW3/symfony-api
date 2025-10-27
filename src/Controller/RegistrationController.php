<?php
namespace OSW3\Api\Controller;

use App\Entity\User;
use OSW3\Api\Service\RouteService;
use OSW3\Api\Service\VersionService;
use Doctrine\ORM\EntityManagerInterface;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use OSW3\Api\Exception\UserAlreadyExistsException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use OSW3\Api\Exception\InvalidRegistrationDataException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegistrationController
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
            if ($this->routeService->resolveRouteName($providerName, $action) === $route) {
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
        $providers = $this->configuration->getProviders();
        $providerName = $this->findMatchingProvider($providers, $route, $action);

        if ($providerName === null) {
            return new JsonResponse(['error' => 'Unknown provider or route'], 400);
        }


        // --- 3. Check if registration is enabled
        if (!$this->configuration->isRegistrationEnabled($providerName)) {
            return new JsonResponse(['error' => 'Registration is disabled'], 403);
        }

        
        // --- 4. Retrieve the provider configuration
        $entityClass = $this->configuration->getSecurityClass($providerName);
        $properties  = $this->configuration->getRegistrationFieldsMapping($providerName);

        if (empty($entityClass)) {
            return new JsonResponse(['error' => 'No security entity defined'], 500);
        }


        // --- 5. Check required fields
        $propertyUsername = $properties['username'] ?? 'email';
        $propertyPassword = $properties['password'] ?? 'password';

        if (empty($data[$propertyUsername]) || empty($data[$propertyPassword])) {
            throw new InvalidRegistrationDataException();
        }

        $username = $data[$propertyUsername];
        // $password = $data[$propertyPassword];


        // --- 6. Check user existence
        $existingUser = $em->getRepository($entityClass)->findOneBy([
            $propertyUsername => $username,
        ]);

        if ($existingUser) {
            throw new UserAlreadyExistsException();
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

    public function verifyEmail(): JsonResponse 
    {
        return new JsonResponse([
            'version' => $this->versionService->getLabel(),
        ], Response::HTTP_OK);
    }

    public function resendVerification(): JsonResponse 
    {
        return new JsonResponse([
            'version' => $this->versionService->getLabel(),
        ], Response::HTTP_OK);
    }
}
