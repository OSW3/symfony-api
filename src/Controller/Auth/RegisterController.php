<?php 
namespace OSW3\Api\Controller\Auth;

use OSW3\Api\Enum\Template\Type;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\TemplateService;
use OSW3\Api\Service\SerializeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use OSW3\Api\Exception\UserAlreadyExistsException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PropertyAccess\PropertyAccess;
use OSW3\Api\Exception\InvalidRegistrationDataException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterController extends AbstractController
{
    public function __construct(
        // private readonly ManagerRegistry $doctrine,
        private readonly ContextService $contextService,
        private readonly TemplateService $templateService,
        private readonly SerializeService $serializeService,
        // private readonly PaginationService $paginationService,
        // private readonly ConfigurationService $configurationService,
    ){
        // $this->provider    = $contextService->getContext('provider');
        // $this->collection  = $contextService->getContext('collection');
        // $this->endpoint    = $contextService->getContext('endpoint');
        // $this->entityClass = $this->collection;
        // $this->repository  = $doctrine->getRepository($this->entityClass);
        // $this->method      = $configurationService->getRepositoryMethod($this->provider, $this->collection, $this->endpoint)['method'] ?? null;
    }

    public function register(
        Request $request, 
        EntityManagerInterface $em, 
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse
    {
        // --- 1. Request context
        $provider   = $this->contextService->getContext('provider');
        $collection = $this->contextService->getContext('collection');
        $endpoint   = $this->contextService->getContext('endpoint');
        

        // --- 2. Validate endpoint
        if (!$this->authenticationService->isEndpointEnabled($provider, $collection, $endpoint)) {
            throw $this->createNotFoundException('Registration endpoint is disabled.');
        }

        if (empty($collection)) {
            throw $this->createNotFoundException('Collection not specified for registration.');
        }


        // --- 3. Handle request data
        $data       = json_decode($request->getContent(), true);
        $classname  = $collection;
        $properties = $this->authenticationService->getProperties($provider, $collection, $endpoint);


        // --- 4. Check required fields
        $identifier = $properties['username'] ?? 'email';
        $password   = $properties['password'] ?? 'password';

        if (
            (!isset($data[$identifier]) || empty($data[$identifier])) || 
            (!isset($data[$password]) || empty($data[$password]))
        ) {
            throw new InvalidRegistrationDataException();
        }


        // --- 5. Check user existence
        $existingUser = $em->getRepository($classname)->findOneBy([
            $identifier => $data[$identifier],
        ]);

        if (!empty($existingUser)) {
            throw new UserAlreadyExistsException();
        }


        // --- 6. Create the new user
        $user = new $classname();
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($properties as $key => $property) {
            if (!array_key_exists($property, $data)) {
                continue;
            }

            $value = ($property === $password)
                ? $passwordHasher->hashPassword($user, $data[$property])
                : $data[$property];

            $accessor->setValue($user, $property, $value);
        }


        // --- 7. Database persistence
        $em->persist($user);
        $em->flush();
        

        // Normalize data
        $normalized = $this->serializeService->normalize($user);

        // Define template type
        $this->templateService->setType(Type::ACCOUNT->value);
        
        // Return response
        return new JsonResponse([
            'message' => 'User registered successfully',
            'user' => $user,
            'normalized' => $normalized,
        ], Response::HTTP_CREATED);
    }
}