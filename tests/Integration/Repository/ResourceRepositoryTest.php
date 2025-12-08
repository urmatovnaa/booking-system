<?php
// tests/Integration/Repository/ResourceRepositoryTest.php
namespace App\Tests\Integration\Repository;

use App\Entity\Resource;
use App\Entity\User;

class ResourceRepositoryTest extends BaseRepositoryTest
{
    public function testRepositoryBasicOperations(): void
    {
        $repository = $this->entityManager->getRepository(Resource::class);
        
        // Сначала создаем пользователя
        $user = new User();
        $user->setEmail('owner@example.com');
        $user->setPassword('password123');
        
        if (method_exists($user, 'setRoles')) {
            $user->setRoles(['ROLE_USER']);
        }
        
        $this->entityManager->persist($user);
        
        // findAll() должен возвращать массив
        $allResources = $repository->findAll();
        $this->assertIsArray($allResources);
        $this->assertCount(0, $allResources, 'Should start with empty table');
        
        // Создаем ресурс
        $resource = new Resource();
        $resource->setName('Conference Room');
        
        // Проверяем и устанавливаем дополнительные поля если они существуют
        if (method_exists($resource, 'setDescription')) {
            $resource->setDescription('Large conference room with projector');
        }
        
        if (method_exists($resource, 'setStatus')) {
            $resource->setStatus('active');
        }
        
        if (method_exists($resource, 'setType')) {
            $resource->setType('room');
        }
        
        if (method_exists($resource, 'setCapacity')) {
            $resource->setCapacity(20);
        }
        
        // Устанавливаем пользователя для ресурса
        $resource->setUser($user);
        
        $this->entityManager->persist($resource);
        $this->entityManager->flush();
        
        // Проверяем что можем найти ресурс по ID
        $foundResource = $repository->find($resource->getId());
        $this->assertNotNull($foundResource);
        $this->assertEquals($resource->getId(), $foundResource->getId());
        $this->assertEquals('Conference Room', $foundResource->getName());
        
        // Проверяем что можем найти ресурс по имени
        $resourceByName = $repository->findOneBy(['name' => 'Conference Room']);
        $this->assertNotNull($resourceByName);
        $this->assertEquals($resource->getId(), $resourceByName->getId());
        
        // Проверяем что findAll теперь возвращает 1 запись
        $allResources = $repository->findAll();
        $this->assertCount(1, $allResources);
        
        // Убрали echo: echo "✅ ResourceRepository basic operations work\n";
    }
    
    public function testFindByName(): void
    {
        $repository = $this->entityManager->getRepository(Resource::class);
        
        // Создаем пользователя
        $user = new User();
        $user->setEmail('test-owner@example.com');
        $user->setPassword('password123');
        
        if (method_exists($user, 'setRoles')) {
            $user->setRoles(['ROLE_USER']);
        }
        
        $this->entityManager->persist($user);
        
        // Создаем несколько ресурсов
        $resource1 = new Resource();
        $resource1->setName('Room A');
        $resource1->setUser($user);
        
        $resource2 = new Resource();
        $resource2->setName('Room B');
        $resource2->setUser($user);
        
        if (method_exists($resource1, 'setStatus')) {
            $resource1->setStatus('active');
            $resource2->setStatus('active');
        }
        
        $this->entityManager->persist($resource1);
        $this->entityManager->persist($resource2);
        $this->entityManager->flush();
        
        // Ищем по имени
        $foundResource = $repository->findOneBy(['name' => 'Room A']);
        $this->assertNotNull($foundResource);
        $this->assertEquals('Room A', $foundResource->getName());
        
        // Проверяем поиск всех ресурсов
        $allResources = $repository->findAll();
        $this->assertCount(2, $allResources);
        
        // Проверяем поиск по массиву критериев
        $resources = $repository->findBy(['name' => 'Room A']);
        $this->assertCount(1, $resources);
        
        // Убрали echo: echo "✅ ResourceRepository can find resources by name\n";
    }
}