<?php

namespace App\Tests;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TestAuthenticator extends AbstractAuthenticator
{
    public function supports(\Symfony\Component\HttpFoundation\Request $request): ?bool
    {
        return true; // Поддерживаем все запросы в тестах
    }

    public function authenticate(\Symfony\Component\HttpFoundation\Request $request): Passport
    {
        // В тестах всегда аутентифицируем как тестового пользователя
        return new SelfValidatingPassport(new UserBadge('test@example.com'));
    }

    public function onAuthenticationSuccess(\Symfony\Component\HttpFoundation\Request $request, TokenInterface $token, string $firewallName): ?\Symfony\Component\HttpFoundation\Response
    {
        return null;
    }

    public function onAuthenticationFailure(\Symfony\Component\HttpFoundation\Request $request, AuthenticationException $exception): ?\Symfony\Component\HttpFoundation\Response
    {
        return null;
    }
}
