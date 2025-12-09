<?php

namespace App\Tests\Traits;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait AuthTestTrait
{
    protected function authClient(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): void
    {
        $container = $client->getContainer(); // ВАЖНО!!!

        $em = $container->get('doctrine')->getManager();
        $userRepository = $em->getRepository(\App\Entity\User::class);

        $user = $userRepository->findOneBy([]);

        if (!$user) {
            $user = new \App\Entity\User();
            $user->setEmail('test@example.com');

            $passwordHasher = $container->get(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface::class);

            $user->setPassword(
                $passwordHasher->hashPassword($user, 'password123')
            );

            $em->persist($user);
            $em->flush();
        }

        $client->loginUser($user);
    }
}
