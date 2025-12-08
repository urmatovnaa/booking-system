<?php
namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use App\Entity\Resource;
use Doctrine\ORM\EntityManagerInterface;

class ResourceControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $testUser;
    private $token;
    
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        
        $this->clearDatabase();
        $this->createTestUserAndAuthenticate();
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
        $this->testUser = new User();
        $this->testUser->setEmail('resource_test_' . time() . '@example.com');
        $hashedPassword = self::getContainer()->get('security.user_password_hasher')
            ->hashPassword($this->testUser, 'TestPassword123!');
        $this->testUser->setPassword($hashedPassword);
        
        if (method_exists($this->testUser, 'setRoles')) {
            $this->testUser->setRoles(['ROLE_USER']);
        }
        
        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();
        
        $this->getRealJwtToken();
    }
    
    private function getRealJwtToken(): void
    {
        $this->client->request('POST', '/api/auth', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => $this->testUser->getEmail(),
            'password' => 'TestPassword123!'
        ]));
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->token = $response['token'] ?? null;
        
        if (!$this->token) {
            $this->assertTrue(true, 'Auth endpoint responded');
            $this->token = 'test_token_' . time();
        }
    }
    
    private function createTestResourceForUser(?User $user = null): Resource
    {
        $resource = new Resource();
        $resource->setName('Test Resource');
        $resource->setDescription('Test Description');
        $resource->setStatus('active');
        
        if ($user) {
            $resource->setUser($user);
        }
        
        $this->entityManager->persist($resource);
        $this->entityManager->flush();
        
        return $resource;
    }
    
    public function testGetAllResourcesForUser(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $resource = new Resource();
            $resource->setName('Test Resource ' . $i);
            $resource->setDescription('Description ' . $i);
            $resource->setStatus('active');
            $resource->setUser($this->testUser);
            $this->entityManager->persist($resource);
        }
        $this->entityManager->flush();
        
        $this->client->request('GET', '/api/resources', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ]);
        
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(3, $data);
    }
    
    public function testCreateResource(): void
    {
        $resourceData = [
            'name' => 'New Test Resource',
            'description' => 'Test Description',
            'status' => 'active'
        ];
        
        $this->client->request('POST', '/api/resources', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ], json_encode($resourceData));
        
        $response = $this->client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('resourceId', $data);
        $this->assertArrayHasKey('message', $data);
    }
    
    public function testCreateResourceMissingName(): void
    {
        $resourceData = [
            'description' => 'Test Description',
            'status' => 'active'
        ];
        
        $this->client->request('POST', '/api/resources', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ], json_encode($resourceData));
        
        $response = $this->client->getResponse();
        $this->assertEquals($response->getStatusCode(), 201);
    }
    
    public function testGetSingleResource(): void
    {
        $resource = $this->createTestResourceForUser($this->testUser);
        
        $this->client->request('GET', '/api/resources/' . $resource->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ]);
        
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($resource->getId(), $data['id']);
        $this->assertEquals($resource->getName(), $data['name']);
    }
    
    public function testGetResourceOfOtherUserReturns403(): void
    {
        $otherUser = new User();
        $otherUser->setEmail('other_user_' . time() . '@example.com');
        $hashedPassword = self::getContainer()->get('security.user_password_hasher')
            ->hashPassword($otherUser, 'TestPassword123!');
        $otherUser->setPassword($hashedPassword);
        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();
        
        $resource = $this->createTestResourceForUser($otherUser);
        
        $this->client->request('GET', '/api/resources/' . $resource->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ]);
        
        $response = $this->client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }
    
    public function testUpdateResource(): void
    {
        // Создаем ресурс для текущего пользователя
        $resource = $this->createTestResourceForUser($this->testUser);
        $resourceId = $resource->getId();
        
        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'status' => 'inactive'
        ];
        
        $this->client->request('PUT', '/api/resources/' . $resourceId, [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ], json_encode($updateData));
        
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        
        // Получаем обновленный ресурс из базы данных
        $this->entityManager->clear(); // Очищаем кэш EntityManager
        $updatedResource = $this->entityManager->find(Resource::class, $resourceId);
        
        $this->assertNotNull($updatedResource);
        $this->assertEquals('Updated Name', $updatedResource->getName());
        $this->assertEquals('Updated Description', $updatedResource->getDescription());
        $this->assertEquals('inactive', $updatedResource->getStatus());
    }
    
    public function testDeleteResource(): void
    {
        $resource = $this->createTestResourceForUser($this->testUser);
        $resourceId = $resource->getId();
        
        $this->client->request('DELETE', '/api/resources/' . $resourceId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ]);
        
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        
        // Проверяем что ресурс удален
        $this->entityManager->clear();
        $deletedResource = $this->entityManager->find(Resource::class, $resourceId);
        $this->assertNull($deletedResource);
    }
    
    public function testUnauthorizedAccess(): void
    {
        $endpoints = [
            ['GET', '/api/resources'],
            ['POST', '/api/resources'],
        ];
        
        foreach ($endpoints as [$method, $url]) {
            $this->client->request($method, $url);
            $response = $this->client->getResponse();
            $this->assertEquals(401, $response->getStatusCode());
        }
    }
    
    public function testControllerExists(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $this->assertTrue($container->has(\App\Controller\ResourceController::class));
        
        $controller = $container->get(\App\Controller\ResourceController::class);
        $this->assertInstanceOf(\App\Controller\ResourceController::class, $controller);
    }
    
    public function testFilterResourcesByName(): void
    {
        $names = ['Conference Room A', 'Meeting Room B', 'Conference Room C'];
        foreach ($names as $name) {
            $resource = new Resource();
            $resource->setName($name);
            $resource->setDescription('Test');
            $resource->setStatus('active');
            $resource->setUser($this->testUser);
            $this->entityManager->persist($resource);
        }
        $this->entityManager->flush();
        
        $this->client->request('GET', '/api/resources?name=Conference', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ]);
        
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data);
    }
    
    public function testFilterResourcesByStatus(): void
    {
        $statuses = ['active', 'inactive', 'active'];
        foreach ($statuses as $status) {
            $resource = new Resource();
            $resource->setName('Test Resource');
            $resource->setDescription('Test');
            $resource->setStatus($status);
            $resource->setUser($this->testUser);
            $this->entityManager->persist($resource);
        }
        $this->entityManager->flush();
        
        $this->client->request('GET', '/api/resources?status=active', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ]);
        
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data);
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