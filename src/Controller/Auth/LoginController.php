<?php 
namespace OSW3\Api\Controller\Auth;

use OSW3\Api\Enum\Template\Type;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\TemplateService;
use OSW3\Api\Service\SerializeService;
use Doctrine\ORM\EntityManagerInterface;
use OSW3\Api\Service\AuthenticationService;
use OSW3\Api\Exception\CredentialsException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final class LoginController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ContextService $contextService,
        private readonly TemplateService $templateService,
        private readonly SerializeService $serializeService,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UserPasswordHasherInterface $passwordEncoder,
        private readonly AuthenticationService $authenticationService,
    ){}
    
    public function login(Request $request): JsonResponse
    {
        // Retrieve request data
        $data = json_decode($request->getContent(), true);

        // Get login properties
        $props         = $this->authenticationService->getProperties();
        $identifierKey = $props['identifier'] ?? 'email';
        $passwordKey   = $props['password'] ?? 'password';
        $identifier    = $data[$identifierKey] ?? null;
        $password      = $data[$passwordKey] ?? null;

        // Get collection (the entity classname)
        $collection = $this->contextService->getCollection();
        $classname  = $collection;


        $user = $this->em->getRepository($classname)
            ->findOneBy(['email' => $identifier]);


        if (!$user || !$this->passwordEncoder->isPasswordValid($user, $password)) {
            throw new CredentialsException();
        }



        
        // Define template type
        $this->templateService->setType(Type::LOGIN->value);
        try {
            $token = $this->jwtManager->create($user);


        
            // Normalize data
            $normalized = $this->serializeService->normalize($user);


            // return new JsonResponse(['token' => $token]);
            // dump($token);
            return $this->json([
                'token' => $token, 
                'user' => $normalized
            ]);
        } catch (\Exception $e) {
            // return new JsonResponse(['error' => 'JWT creation failed: '.$e->getMessage()], 500);
            return $this->json(['error' => 'JWT creation failed: '.$e->getMessage()], 500);
        }


        
        // dd($data, $props, $classname, $user);
    }

    public function refresh()
    {
        // Refresh logic goes here
    }
}