<?php

namespace App\Tests\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

trait TestUserTrait
{
    private function createTestUser(EntityManagerInterface $em, UserPasswordHasherInterface $hasher)
    {
        $userClass = class_exists('App\Entity\User')
            ? 'App\Entity\User'
            : 'App\Domain\User\Entity\User';

        $repo = $em->getRepository($userClass);
        $user = $repo->findOneBy(['email' => 'test@example.com']);

        if ($user) {
            return $user;
        }

        $user = new $userClass();
        $user->setEmail('test@example.com');
        $user->setPassword($hasher->hashPassword($user, 'password123'));

        $em->persist($user);
        $em->flush();

        return $user;
    }
}
