<?php

namespace App\Tests\Integration\Controller;

use App\Tests\Traits\AuthTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiAuthControllerTest extends WebTestCase
{   
    use AuthTestTrait;

    public function testRegisterEndpointExists(): void
    {
        $client = static::createClient();
        $this->authClient($client);
        
        // Просто проверяем что endpoint существует и отвечает
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]));
        
        $statusCode = $client->getResponse()->getStatusCode();
        
        // Не должно быть 404 (endpoint не найден) или 405 (метод не разрешен)
        $this->assertNotEquals(404, $statusCode, 'Endpoint /api/register should exist');
        $this->assertNotEquals(405, $statusCode, 'POST method should be allowed for /api/register');
        
        echo "✅ /api/register endpoint exists (HTTP $statusCode)\n";
        
        // Логируем ответ для отладки
        $response = $client->getResponse()->getContent();
        if (strlen($response) < 1000) { // Не логируем огромные ответы
            echo "Response: $response\n";
        }
    }
    
    public function testLoginEndpointExists(): void
    {
        $client = static::createClient();
        
        // Изменяем здесь: используем /api/auth вместо /api/login
        $client->request('POST', '/api/auth', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'password' => 'Password123!'
        ]));
        
        $statusCode = $client->getResponse()->getStatusCode();
        
        // Проверяем что endpoint существует
        $this->assertNotEquals(404, $statusCode, 'Endpoint /api/auth should exist');
        $this->assertNotEquals(405, $statusCode, 'POST method should be allowed for /api/auth');
        
        echo "✅ /api/auth endpoint exists (HTTP $statusCode)\n";
    }
    
    public function testEndpointsReturnJson(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT'  => 'application/json' 
        ], json_encode([
            'email' => 'check_json_user@example.com',
            'password' => 'password123'
        ]));

        $response = $client->getResponse();
        
        if ($response->getStatusCode() === 500) {
            fwrite(STDERR, "\nSERVER ERROR: " . $response->getContent() . "\n");
        }

        $this->assertJson($response->getContent());
    }
}