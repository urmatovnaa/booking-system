<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class SimpleAuthController extends AbstractController
{
    #[Route("/simple-login", name: "simple_login", methods: ["POST"])]
    public function simpleLogin(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data["email"]) || !isset($data["password"])) {
            return $this->json(["error" => "Email and password are required"], Response::HTTP_BAD_REQUEST);
        }

        // Ищем пользователя
        $user = $entityManager->getRepository(User::class)->findOneBy(["email" => $data["email"]]);
        
        if (!$user) {
            return $this->json(["error" => "User not found"], Response::HTTP_UNAUTHORIZED);
        }

        // Проверяем пароль
        if (!$passwordHasher->isPasswordValid($user, $data["password"])) {
            return $this->json(["error" => "Invalid password"], Response::HTTP_UNAUTHORIZED);
        }

        // Простой ответ - просто подтверждаем что логин работает
        return $this->json([
            "message" => "Login successful! 🎉",
            "user" => [
                "id" => $user->getId(),
                "email" => $user->getEmail()
            ]
        ]);
    }

    #[Route("/simple-register", name: "simple_register", methods: ["POST"])]
    public function simpleRegister(
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
}
