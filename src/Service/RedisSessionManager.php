<?php
namespace App\Service;

use Predis\Client;

class RedisSessionManager
{
    private Client $redis;

    public function __construct(string $redisHost, string $redisPort)
    {
        $this->redis = new Client([
            'host' => $redisHost,
            'port' => $redisPort,
            'timeout' => 2.0,
        ]);
    }

    public function storeUserSession(string $token, array $userData): void
    {
        $sessionKey = 'jwt_session:' . md5($token);
        $this->redis->setex($sessionKey, 3600, json_encode($userData));
        
        $userId = $userData['userId'];
        $this->redis->sadd("user_sessions:{$userId}", $sessionKey);
    }

    public function getUserSession(string $token): ?array
    {
        $sessionKey = 'jwt_session:' . md5($token);
        $session = $this->redis->get($sessionKey);
        return $session ? json_decode($session, true) : null;
    }

    public function refreshSession(string $token): void
    {
        $sessionKey = 'jwt_session:' . md5($token);
        if ($this->redis->exists($sessionKey)) {
            $this->redis->expire($sessionKey, 3600);
        }
    }

    public function removeUserSession(string $token): void
    {
        $sessionKey = 'jwt_session:' . md5($token);
        $session = $this->getUserSession($token);
        if ($session) {
            $userId = $session['userId'];
            $this->redis->del($sessionKey);
            $this->redis->srem("user_sessions:{$userId}", $sessionKey);
        }
    }

    public function removeAllUserSessions(int $userId): void
    {
        $sessionKeys = $this->redis->smembers("user_sessions:{$userId}");
        if (!empty($sessionKeys)) {
            $this->redis->del($sessionKeys);
            $this->redis->del("user_sessions:{$userId}");
        }
    }

    public function cacheGetRequest(string $cacheKey, $data, int $ttl = 300): void
    {
        $this->redis->setex("cache:{$cacheKey}", $ttl, serialize($data));
    }

    public function getCachedRequest(string $cacheKey)
    {
        $data = $this->redis->get("cache:{$cacheKey}");
        return $data ? unserialize($data) : null;
    }
}
