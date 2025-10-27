<?php 
// src/Controller/Api/LoginController.php
namespace OSW3\Api\Controller;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final class AuthenticationController
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private UserPasswordHasherInterface $passwordEncoder,
        private EntityManagerInterface $em,
    ) {}

    public function login(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;


        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email and password required'], 400);
        }

        // Récupérer l'utilisateur via UserProvider
        $user = $this->em->getRepository(\App\Entity\User::class)
            ->findOneBy(['email' => $email]);



        if (!$user || !$this->passwordEncoder->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }



        try {
            $token = $this->jwtManager->create($user);
            return new JsonResponse(['token' => $token]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'JWT creation failed: '.$e->getMessage()], 500);
        }

    }

    public function logout(UserInterface $user)
    {
        // In JWT, logout is typically handled on the client side by deleting the token.
        // Optionally, you can implement token blacklisting here.

        return new JsonResponse(['message' => 'Logout successful']);
    }

    public function refreshToken(UserInterface $user)
    {
        try {
            $token = $this->jwtManager->create($user);
            return new JsonResponse(['token' => $token]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'JWT refresh failed: '.$e->getMessage()], 500);
        }
    }
}