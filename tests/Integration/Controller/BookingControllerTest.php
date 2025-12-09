<?php
namespace App\Tests\Integration\Controller;


use App\Tests\Traits\AuthTestTrait;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use App\Entity\Resource;
use App\Entity\Booking;
use Doctrine\ORM\EntityManagerInterface;

class BookingControllerTest extends WebTestCase
{   
    use AuthTestTrait;

    private $client;
    private $entityManager;
    private $testUser;
    private $testResource;
    private $token;
    
    protected function setUp(): void
    {   
        $this->client = static::createClient();
        
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        
        $this->createSchemaIfNotExists();
        
        $this->clearDatabase();
        
        $this->createTestUserAndAuthenticate();
        $this->createTestResource();
    }
    
    private function createSchemaIfNotExists(): void
    {
        $schemaManager = $this->entityManager->getConnection()->createSchemaManager();
        
        // Проверяем ВСЕ нужные таблицы
        $requiredTables = ['booking', 'resource', 'user'];
        $allTablesExist = true;
        
        foreach ($requiredTables as $table) {
            if (!$schemaManager->tablesExist([$table])) {
                $allTablesExist = false;
                break;
            }
        }
        
        if (!$allTablesExist) {
            // Создаем недостающие таблицы
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
            $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
            
            try {
                // Сначала удаляем существующие таблицы (осторожно!)
                $schemaTool->dropSchema($metadata);
            } catch (\Exception $e) {
                // Игнорируем ошибки удаления
            }
            
            // Создаем все таблицы заново
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
        $this->client->request('POST', '/api/auth', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT'  => 'application/json' // Request JSON explicitly
        ], json_encode([
            'email' => $this->testUser->getEmail(),
            'password' => 'TestPassword123!'
        ]));
        
        $response = $this->client->getResponse();
        $content = $response->getContent();
        
        if ($response->getStatusCode() !== 200) {
            $errorMsg = $content;
            $json = json_decode($content, true);
            
            if ($json) {
                $errorMsg = json_encode($json, JSON_PRETTY_PRINT);
            } else {
                if (preg_match('/<title>(.*?)<\/title>/', $content, $matches)) {
                    $errorMsg = "HTML Title: " . $matches[1];
                }
            }

            throw new \RuntimeException(sprintf(
                "Auth failed. Status: %d. Error: %s", 
                $response->getStatusCode(), 
                $errorMsg
            ));
        }
        
        $data = json_decode($content, true);
        $this->token = $data['token'] ?? null;
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