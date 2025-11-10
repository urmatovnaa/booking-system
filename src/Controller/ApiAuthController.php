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

class ApiAuthController extends AbstractController
{
    #[Route("/api/register", name: "api_register", methods: ["POST"])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $JWTManager // Добавляем JWT менеджер
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

        // Генерируем токен сразу после регистрации
        $token = $JWTManager->create($user);

        return $this->json([
            "message" => "User registered successfully",
            "token" => $token, // Возвращаем токен
            "user" => [
                "id" => $user->getId(),
                "email" => $user->getEmail()
            ]
        ], Response::HTTP_CREATED);
    }

    // Остальной код без изменений...
    #[Route("/api/auth", name: "api_auth", methods: ["POST"])]
    public function auth(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $JWTManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data["email"]) || !isset($data["password"])) {
            return $this->json(["error" => "Email and password are required"], Response::HTTP_BAD_REQUEST);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(["email" => $data["email"]]);
        
        if (!$user) {
            return $this->json(["error" => "User not found"], Response::HTTP_UNAUTHORIZED);
        }

        if (!$passwordHasher->isPasswordValid($user, $data["password"])) {
            return $this->json(["error" => "Invalid password"], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            "message" => "Login successful! 🎉",
            "token" => $JWTManager->create($user),
            "user" => [
                "id" => $user->getId(),
                "email" => $user->getEmail()
            ]
        ]);
    }
}