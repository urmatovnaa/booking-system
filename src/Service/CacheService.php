<?php

namespace App\Service;

use Symfony\Component\Cache\Adapter\RedisAdapter;

class CacheService
{
    public function __construct()
    {
    }

    public static function getCached(string $key, callable $callback, int $ttl = 300): mixed
    {
        try {
            $redisConnection = RedisAdapter::createConnection('redis://redis:6379');
            $cache = new RedisAdapter($redisConnection);
            
            $item = $cache->getItem($key);
            
            if (!$item->isHit()) {
                $value = $callback();
                $item->set($value);
                $item->expiresAfter($ttl);
                $cache->save($item);
                return $value;
            }
            
            return $item->get();
        } catch (\Exception $e) {
            error_log('Redis cache error: ' . $e->getMessage());
            return $callback();
        }
    }

    public static function delete(string $key): bool
    {
        try {
            $redisConnection = RedisAdapter::createConnection('redis://redis:6379');
            $cache = new RedisAdapter($redisConnection);
            return $cache->deleteItem($key);
        } catch (\Exception $e) {
            error_log('Redis delete error: ' . $e->getMessage());
            return false;
        }
    }
}