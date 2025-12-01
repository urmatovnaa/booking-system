<?php
namespace App\EventSubscriber;

use App\Service\RedisSessionManager;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTRedisSubscriber implements EventSubscriberInterface
{
    private RedisSessionManager $redisSessionManager;

    public function __construct(RedisSessionManager $redisSessionManager)
    {
        $this->redisSessionManager = $redisSessionManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticationSuccessEvent::class => 'onAuthenticationSuccess',
            JWTDecodedEvent::class => 'onJWTDecoded',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();
        $token = $data['token'] ?? null;

        if ($token && $user) {
            $this->redisSessionManager->storeUserSession($token, [
                'userId' => $user->getId(),
                'email' => $user->getEmail(),
                'createdAt' => time()
            ]);
        }
    }

    public function onJWTDecoded(JWTDecodedEvent $event): void
    {
        $token = $event->getToken();

        if (is_object($token) && method_exists($token, 'getCredentials')) {
            $raw = $token->getCredentials();
        } elseif (is_string($token)) {
            $raw = $token;
        } else {
            $raw = null;
        }

        if (!$raw || !$this->redisSessionManager->getUserSession($raw)) {
            $event->markAsInvalid();
        } else {
            $this->redisSessionManager->refreshSession($raw);
        }
    }
}
