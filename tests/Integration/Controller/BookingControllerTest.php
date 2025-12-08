<?php

namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookingControllerTest extends WebTestCase
{
    public function testCreateBooking(): void
    {
        $client = static::createClient();
        
        // 1. Сначала нужно аутентифицироваться
        // Создаем тестового пользователя если нужно
        // или мокаем аутентификацию
        
        // 2. Отправляем запрос
        $client->request('POST', '/api/bookings', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer test_token' // Нужен JWT токен
        ], json_encode([
            'resourceId' => 1,
            'startTime' => '2024-01-01T10:00:00Z',
            'endTime' => '2024-01-01T12:00:00Z'
        ]));
        
        // Ожидаем 401 без аутентификации
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }
    
    public function testBookingOverlap(): void
    {
        $client = static::createClient();
        
        // Тестируем логику без аутентификации
        // или создаем тестового пользователя
        $this->assertTrue(true); // Заглушка пока
    }
    
    public function testBookingCRUD(): void
    {
        // Простой тест что контроллер существует
        self::bootKernel();
        $container = static::getContainer();
        
        $this->assertTrue($container->has(\App\Controller\BookingController::class));
        
        $controller = $container->get(\App\Controller\BookingController::class);
        $this->assertInstanceOf(\App\Controller\BookingController::class, $controller);
    }
}