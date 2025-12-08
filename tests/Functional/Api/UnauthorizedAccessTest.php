<?php
// tests/Functional/Api/UnauthorizedAccessTest.php
namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UnauthorizedAccessTest extends WebTestCase
{
    public function testResourcesIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        // Без авторизации
        $client->request('GET', '/api/resources');
        
        // Должен вернуть 401 или редирект
        $statusCode = $client->getResponse()->getStatusCode();
        
        // Принимаем либо 401 (Unauthorized), либо 403 (Forbidden)
        $this->assertTrue(
            in_array($statusCode, [401, 403]),
            "Expected 401 or 403 for unauthorized access, got {$statusCode}"
        );
    }
    
    public function testBookingsIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        // Без авторизации
        $client->request('GET', '/api/bookings');
        
        // Должен вернуть 401 или редирект
        $statusCode = $client->getResponse()->getStatusCode();
        
        // Принимаем либо 401 (Unauthorized), либо 403 (Forbidden)
        $this->assertTrue(
            in_array($statusCode, [401, 403]),
            "Expected 401 or 403 for unauthorized access, got {$statusCode}"
        );
    }
}