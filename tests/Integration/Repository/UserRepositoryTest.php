<?php
// tests/Integration/Repository/UserRepositoryTest.php
namespace App\Tests\Integration\Repository;

use App\Entity\User;

class UserRepositoryTest extends BaseRepositoryTest
{
    public function testRepositoryBasicOperations(): void
    {
        $repository = $this->entityManager->getRepository(User::class);
        
        // findAll() должен возвращать массив
        $allUsers = $repository->findAll();
        $this->assertIsArray($allUsers);
        $this->assertCount(0, $allUsers, 'Should start with empty table');
        
        // Создаем пользователя
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password123');
        
        if (method_exists($user, 'setRoles')) {
            $user->setRoles(['ROLE_USER']);
        }
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // Проверяем что можем найти пользователя по ID
        $foundUser = $repository->find($user->getId());
        $this->assertNotNull($foundUser);
        $this->assertEquals($user->getId(), $foundUser->getId());
        $this->assertEquals('test@example.com', $foundUser->getEmail());
        
        // Проверяем что можем найти пользователя по email
        $userByEmail = $repository->findOneBy(['email' => 'test@example.com']);
        $this->assertNotNull($userByEmail);
        $this->assertEquals($user->getId(), $userByEmail->getId());
        
        // Проверяем что findAll теперь возвращает 1 запись
        $allUsers = $repository->findAll();
        $this->assertCount(1, $allUsers);
        
        // Убрали echo: echo "✅ UserRepository basic operations work\n";
    }
    
    public function testFindByEmail(): void
    {
        $repository = $this->entityManager->getRepository(User::class);
        
        // Создаем пользователя
        $user = new User();
        $user->setEmail('unique@example.com');
        $user->setPassword('password123');
        
        if (method_exists($user, 'setRoles')) {
            $user->setRoles(['ROLE_USER']);
        }
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // Ищем по email
        $foundUser = $repository->findOneBy(['email' => 'unique@example.com']);
        $this->assertNotNull($foundUser);
        $this->assertEquals('unique@example.com', $foundUser->getEmail());
        
        // Проверяем что не находим несуществующего пользователя
        $notFoundUser = $repository->findOneBy(['email' => 'nonexistent@example.com']);
        $this->assertNull($notFoundUser);
        
        // Убрали echo: echo "✅ UserRepository can find users by email\n";
    }
    
    public function testUserCount(): void
    {
        $repository = $this->entityManager->getRepository(User::class);
        
        // Создаем несколько пользователей
        for ($i = 1; $i <= 3; $i++) {
            $user = new User();
            $user->setEmail("user{$i}@example.com");
            $user->setPassword("password{$i}");
            
            if (method_exists($user, 'setRoles')) {
                $user->setRoles(['ROLE_USER']);
            }
            
            $this->entityManager->persist($user);
        }
        
        $this->entityManager->flush();
        
        // Проверяем количество пользователей
        $allUsers = $repository->findAll();
        $this->assertCount(3, $allUsers);
        
        // Убрали echo: echo "✅ UserRepository returns correct count of users\n";
    }
}