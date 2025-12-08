<?php

namespace App\Tests\Test;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TestJwtAuthenticator extends AbstractAuthenticator
{
    public function supports(\Symfony\Component\HttpFoundation\Request $request): ?bool
    {
        // Поддерживаем все запросы в тестах
        return $request->headers->has('Authorization') || 
               str_starts_with($request->getPathInfo(), '/api/');
    }

    public function authenticate(\Symfony\Component\HttpFoundation\Request $request): Passport
    {
        // В тестах всегда возвращаем тестового пользователя
        $userIdentifier = 'test@example.com';
        
        // Или извлекаем из токена если есть
        $authHeader = $request->headers->get('Authorization');
        if ($authHeader && preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            // Можно декодировать JWT токен для тестов
            $userIdentifier = 'user_from_token@example.com';
        }
        
        return new SelfValidatingPassport(new UserBadge($userIdentifier));
    }

    public function onAuthenticationSuccess(\Symfony\Component\HttpFoundation\Request $request, TokenInterface $token, string $firewallName): ?\Symfony\Component\HttpFoundation\Response
    {
        return null; // Успех
    }

    public function onAuthenticationFailure(\Symfony\Component\HttpFoundation\Request $request, AuthenticationException $exception): ?\Symfony\Component\HttpFoundation\Response
    {
        return null;
    }
}
