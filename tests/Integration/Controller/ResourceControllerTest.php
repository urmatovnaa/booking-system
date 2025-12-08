<?php
// tests/Integration/Controller/ResourceControllerTest.php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResourceControllerTest extends WebTestCase
{
    public function testEndpointsRequireAuthentication(): void
    {
        $client = static::createClient();
        
        // Устанавливаем переменные окружения
        putenv('REDIS_HOST=localhost');
        
        // Проверяем что защищенные endpoint'ы требуют авторизации
        $endpoints = [
            'GET' => '/api/resources',
            'POST' => '/api/resources',
            'GET' => '/api/resources/1',
            'PUT' => '/api/resources/1',
            'DELETE' => '/api/resources/1',
        ];
        
        foreach ($endpoints as $method => $url) {
            $client->request($method, $url);
            
            // Должен вернуть 401 (Unauthorized) или 403 (Forbidden)
            $statusCode = $client->getResponse()->getStatusCode();
            $this->assertTrue(
                in_array($statusCode, [401, 403]),
                "{$method} {$url} should require authentication (got {$statusCode})"
            );
        }
    }
    
    public function testResourceEndpointsExist(): void
    {
        $client = static::createClient();
        
        // Просто проверяем что endpoint'ы существуют (возвращают не 404)
        $client->request('GET', '/api/resources');
        $this->assertNotEquals(404, $client->getResponse()->getStatusCode());
        
        $client->request('POST', '/api/resources', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));
        $this->assertNotEquals(404, $client->getResponse()->getStatusCode());
    }
}