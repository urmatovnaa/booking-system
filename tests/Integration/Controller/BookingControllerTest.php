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
        
        // Создаем схему если не существует
        $this->createSchemaIfNotExists();
        
        // Очищаем базу данных
        $this->clearDatabase();
        
        $this->createTestUserAndAuthenticate();
        $this->createTestResource();
    }
    
    private function createSchemaIfNotExists(): void
    {
        $schemaManager = $this->entityManager->getConnection()->createSchemaManager();
        
        // Проверяем основные таблицы
        $tables = ['booking', 'resource', 'user'];
        $missingTables = [];
        
        foreach ($tables as $table) {
            if (!$schemaManager->tablesExist([$table])) {
                $missingTables[] = $table;
            }
        }
        
        if (!empty($missingTables)) {
            // Создаем недостающие таблицы
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
            $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
            $schemaTool->createSchema($metadata);
        }
    }
    
    private function clearDatabase(): void
    {
        $schemaManager = $this->entityManager->getConnection()->createSchemaManager();
        
        try {
            // Очищаем только существующие таблицы
            if ($schemaManager->tablesExist(['booking'])) {
                $this->entityManager->createQuery('DELETE FROM App\Entity\Booking')->execute();
            }
            
            if ($schemaManager->tablesExist(['resource'])) {
                $this->entityManager->createQuery('DELETE FROM App\Entity\Resource')->execute();
            }
            
            if ($schemaManager->tablesExist(['user'])) {
                $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
            }
            
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // Игнорируем ошибки если таблицы не существуют
            // Схема будет создана в следующем шаге
        }
    }
    
    private function createTestUserAndAuthenticate(): void
    {
        // Создаем тестового пользователя
        $this->testUser = new User();
        $this->testUser->setEmail('booking_test_' . time() . '@example.com');
        
        // Получаем хешер паролей
        $hasher = self::getContainer()->get('security.user_password_hasher');
        $hashedPassword = $hasher->hashPassword($this->testUser, 'TestPassword123!');
        $this->testUser->setPassword($hashedPassword);
        
        if (method_exists($this->testUser, 'setRoles')) {
            $this->testUser->setRoles(['ROLE_USER']);
        }
        
        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();
        
        // Получаем JWT токен
        $this->getRealJwtToken();
    }
    
    private function getRealJwtToken(): void
    {
        // Пробуем получить токен
        $this->client->request('POST', '/api/auth', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => $this->testUser->getEmail(),
            'password' => 'TestPassword123!'
        ]));
        
        $response = $this->client->getResponse();
        
        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getContent(), true);
            $this->token = $data['token'] ?? null;
        }
        
        // Если не удалось получить токен, создаем мок
        if (!$this->token) {
            $this->token = 'mock_jwt_token_for_testing';
        }
    }
    
    private function createTestResource(): void
    {
        $this->testResource = new Resource();
        $this->testResource->setName('Test Conference Room');
        
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
        ]));
        
        $response = $this->client->getResponse();
        $this->assertContains($response->getStatusCode(), [400, 422]);
    }
    
    public function testGetUserBookings(): void
    {
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
    }
    
    public function testDeleteBooking(): void
    {
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
        
        $this->assertTrue($container->has(\App\Controller\BookingController::class));
        
        $controller = $container->get(\App\Controller\BookingController::class);
        $this->assertInstanceOf(\App\Controller\BookingController::class, $controller);
        
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