<?php

namespace App\Tests\Traits;

use App\Entity\User;

trait AuthTestTrait
{
    private static $testUser;
    private static $authToken;
    
    /**
     * Быстрая настройка для всех тестов
     */
    protected function setUpAuth(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        
        // 1. Создаем тестового пользователя если его нет
        $em = $container->get('doctrine')->getManager();
        $userRepository = $em->getRepository(User::class);
        
        self::$testUser = $userRepository->findOneBy(['email' => 'test@example.com']);
        
        if (!self::$testUser) {
            self::$testUser = new User();
            self::$testUser->setEmail('test@example.com');
            self::$testUser->setPassword('password123'); // или захешируйте
            self::$testUser->setName('Test User');
            
            $em->persist(self::$testUser);
            $em->flush();
        }
        
        // 2. Авторизуем пользователя (без реального логина через API)
        $client->loginUser(self::$testUser);
    }
    
    /**
     * Добавляет заголовок Authorization к существующему клиенту
     */
    protected function addAuthHeader($client): void
    {
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer test_token');
    }
}