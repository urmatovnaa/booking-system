<?php
namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use App\Entity\Resource;
use App\Entity\Booking;
use Doctrine\ORM\EntityManagerInterface;

class BookingControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $testUser;
    private $testResource;
    private $token;
    
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        
        // Очищаем базу данных
        $this->clearDatabase();
        
        $this->createTestUserAndAuthenticate();
        $this->createTestResource();
    }
    
    private function clearDatabase(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\Booking')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Resource')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $this->entityManager->flush();
    }
    
    private function createTestUserAndAuthenticate(): void
    {
        // Создаем тестового пользователя с реальным паролем
        $this->testUser = new User();
        $this->testUser->setEmail('booking_test_' . time() . '@example.com');
        // Хешируем пароль как в реальном приложении
        $hashedPassword = self::getContainer()->get('security.user_password_hasher')
            ->hashPassword($this->testUser, 'TestPassword123!');
        $this->testUser->setPassword($hashedPassword);
        
        if (method_exists($this->testUser, 'setRoles')) {
            $this->testUser->setRoles(['ROLE_USER']);
        }
        
        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();
        
        // Получаем реальный JWT токен
        $this->getRealJwtToken();
    }
    
    private function getRealJwtToken(): void
    {
        // Логинимся для получения реального токена
        $this->client->request('POST', '/api/auth', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => $this->testUser->getEmail(),
            'password' => 'TestPassword123!'
        ]));
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->token = $response['token'] ?? null;
        
        if (!$this->token) {
            throw new \RuntimeException('Failed to get JWT token');
        }
    }
    
    private function createTestResource(): void
    {
        // Создаем тестовый ресурс
        $this->testResource = new Resource();
        $this->testResource->setName('Test Conference Room');
        // Используем только существующие методы
        if (method_exists($this->testResource, 'setType')) {
            $this->testResource->setType('room');
        }
        if (method_exists($this->testResource, 'setCapacity')) {
            $this->testResource->setCapacity(10);
        }
        
        $this->entityManager->persist($this->testResource);
        $this->entityManager->flush();
    }
    
    public function testUnauthorizedAccessReturns401(): void
    {
        // Запрос без токена
        $this->client->request('GET', '/api/bookings');
        $this->assertEquals(401, $this->client->getResponse()->getStatusCode());
    }
    
    public function testCreateBookingSuccess(): void
    {
        $this->client->request('POST', '/api/bookings', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ], json_encode([
            'resourceId' => $this->testResource->getId(),
            'startTime' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'endTime' => date('Y-m-d H:i:s', strtotime('+2 hours')),
            'status' => 'confirmed'
        ]));
        
        $response = $this->client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
    }
    
    public function testCreateBookingMissingRequiredFields(): void
    {
        $this->client->request('POST', '/api/bookings', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ], json_encode([
            'startTime' => date('Y-m-d H:i:s', strtotime('+1 hour'))
            // Нет resourceId и endTime
        ]));
        
        $response = $this->client->getResponse();
        // Может быть 400 или 422 в зависимости от валидации
        $this->assertContains($response->getStatusCode(), [400, 422]);
    }
    
    public function testGetUserBookings(): void
    {
        // Создаем тестовое бронирование
        $booking = new Booking();
        $booking->setResource($this->testResource);
        $booking->setUser($this->testUser);
        $booking->setStartTime(new \DateTime('+1 hour'));
        $booking->setEndTime(new \DateTime('+2 hours'));
        $booking->setStatus('confirmed');
        
        $this->entityManager->persist($booking);
        $this->entityManager->flush();
        
        $this->client->request('GET', '/api/bookings', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ]);
        
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }
    
    public function testDeleteBooking(): void
    {
        // Создаем тестовое бронирование
        $booking = new Booking();
        $booking->setResource($this->testResource);
        $booking->setUser($this->testUser);
        $booking->setStartTime(new \DateTime('+1 hour'));
        $booking->setEndTime(new \DateTime('+2 hours'));
        $booking->setStatus('confirmed');
        
        $this->entityManager->persist($booking);
        $this->entityManager->flush();
        $bookingId = $booking->getId();
        
        $this->client->request('DELETE', '/api/bookings/' . $bookingId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ]);
        
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testControllerMethodsExist(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        // Проверяем что контроллер существует
        $this->assertTrue($container->has(\App\Controller\BookingController::class));
        
        $controller = $container->get(\App\Controller\BookingController::class);
        $this->assertInstanceOf(\App\Controller\BookingController::class, $controller);
        
        // Проверяем основные методы через рефлексию
        $reflection = new \ReflectionClass($controller);
        $methods = ['index', 'create', 'update', 'delete', 'show'];
        
        foreach ($methods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Method $method should exist");
        }
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }
}