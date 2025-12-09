<?php

namespace App\Tests\Integration\Controller;

use App\Tests\Traits\AuthTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiAuthControllerTest extends WebTestCase
{   
    use AuthTestTrait;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->setUpAuth();
    }

    public function testRegisterEndpointExists(): void
    {
        $client = static::createClient();
        
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
        
        // Обновляем здесь: используем /api/auth вместо /api/login
        $endpoints = [
            '/api/register' => ['email' => 'test@example.com', 'password' => 'pass'],
            '/api/auth' => ['email' => 'test@example.com', 'password' => 'pass'], // Изменено
        ];
        
        foreach ($endpoints as $endpoint => $data) {
            $client->request('POST', $endpoint, [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode($data));
            
            $response = $client->getResponse();
            
            // Проверяем Content-Type
            $contentType = $response->headers->get('Content-Type');
            $this->assertStringContainsString('application/json', $contentType ?? '', 
                "Response from $endpoint should be JSON");
            
            // Проверяем что ответ можно декодировать как JSON
            $content = $response->getContent();
            if ($content) {
                json_decode($content);
                $this->assertEquals(JSON_ERROR_NONE, json_last_error(), 
                    "Response from $endpoint should be valid JSON");
            }
            
            echo "✅ $endpoint returns JSON\n";
        }
    }
}