<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ApiAuthController extends AbstractController
{
    #[Route("/api/register", name: "api_register", methods: ["POST"])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data["email"]) || !isset($data["password"])) {
            return $this->json(["error" => "Email and password are required"], Response::HTTP_BAD_REQUEST);
        }

        $existingUser = $entityManager->getRepository(User::class)->findOneBy(["email" => $data["email"]]);
        if ($existingUser) {
            return $this->json(["error" => "User with this email already exists"], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($data["email"]);
        $hashedPassword = $passwordHasher->hashPassword($user, $data["password"]);
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            "message" => "User registered successfully",
            "userId" => $user->getId(),
            "email" => $user->getEmail()
        ], Response::HTTP_CREATED);
    }

    #[Route("/api/login", name: "api_login", methods: ["POST"])]
    public function login(
        #[CurrentUser] ?User $user,
        JWTTokenManagerInterface $JWTManager
    ): JsonResponse {
        if (null === $user) {
            return $this->json([
                "error" => "Invalid credentials"
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            "message" => "Login successful",
            "token" => $JWTManager->create($user),
            "user" => [
                "id" => $user->getId(),
                "email" => $user->getEmail()
            ]
        ]);
    }
}
