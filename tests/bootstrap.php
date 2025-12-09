<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/.env.test')) {
    (new Dotenv())->load(dirname(__DIR__).'/.env.test');
}

// Глобальная настройка перед каждым тестом
register_shutdown_function(function() {
    // Автоматически создаем пользователя перед тестами
    if (isset($GLOBALS['TEST_SETUP_DONE'])) {
        return;
    }
    
    $GLOBALS['TEST_SETUP_DONE'] = true;
    
    // Создаем ядро для тестов
    $kernel = new \App\Kernel('test', false);
    $kernel->boot();
    $container = $kernel->getContainer();
    
    $em = $container->get('doctrine')->getManager();
    
    // Ищем класс User
    $userClass = null;
    $classes = ['App\Entity\User', 'App\Domain\User\Entity\User'];
    foreach ($classes as $class) {
        if (class_exists($class)) {
            $userClass = $class;
            break;
        }
    }
    
    if ($userClass) {
        $userRepository = $em->getRepository($userClass);
        $user = $userRepository->findOneBy(['email' => 'test@example.com']);
        
        if (!$user) {
            $user = new $userClass();
            
            if (method_exists($user, 'setEmail')) {
                $user->setEmail('test@example.com');
            }
            if (method_exists($user, 'setUsername')) {
                $user->setUsername('testuser');
            }
            if (method_exists($user, 'setPassword')) {
                $passwordHasher = $container->get('security.user_password_hasher');
                $user->setPassword($passwordHasher->hashPassword($user, 'password123'));
            }
            
            $em->persist($user);
            $em->flush();
        }
    }
});