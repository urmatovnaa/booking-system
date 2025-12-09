<?php

namespace App\Tests\Traits;

trait AuthTestTrait
{
    /**
     * Просто авторизует клиента без создания свойств
     */
    protected function authClient(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): void
    {
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        
        // Находим любого пользователя
        $userRepository = $em->getRepository(\App\Entity\User::class);
        $user = $userRepository->findOneBy([]);
        
        if (!$user) {
            // Создаем пользователя на лету
            $user = new \App\Entity\User();
            $user->setEmail('test@example.com');
            $user->setName('Test User');
            
            $passwordHasher = $container->get('security.user_password_hasher');
            $user->setPassword(
                $passwordHasher->hashPassword($user, 'password123')
            );
            
            $em->persist($user);
            $em->flush();
        }
        
        // Авторизуем
        $client->loginUser($user);
    }
}