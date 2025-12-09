<?php

namespace App\Tests\Traits;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait AuthTestTrait
{
    protected function authClient(KernelBrowser $client = null): KernelBrowser
    {
        // Если клиент не передан — создаём
        $client = $client ?? static::createClient();

        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        // Ищем существующего пользователя
        $userRepository = $em->getRepository(\App\Entity\User::class);
        $user = $userRepository->findOneBy([]);

        if (!$user) {
            $user = new \App\Entity\User();
            $user->setEmail('test@example.com');

            $passwordHasher = $container->get('security.user_password_hasher');
            $user->setPassword(
                $passwordHasher->hashPassword($user, 'password123')
            );

            $em->persist($user);
            $em->flush();
        }

        // Авторизация
        $client->loginUser($user);

        return $client;
    }
}
