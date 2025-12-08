<?php
// tests/Integration/Service/RedisSessionManagerIntegrationTest.php
namespace App\Tests\Integration\Service;

use App\Service\RedisSessionManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RedisSessionManagerTest extends KernelTestCase
{
    private ?RedisSessionManager $sessionManager;
    
    protected function setUp(): void
    {
        self::bootKernel();
        
        // Проверяем доступность Redis
        $container = static::getContainer();
        
        // Пытаемся создать менеджер с тестовыми параметрами
        try {
            $this->sessionManager = new RedisSessionManager('redis', '6379');
            
            // Проверяем подключение
            $this->sessionManager->cacheGetRequest('test_connection', 'ping', 1);
        } catch (\Exception $e) {
            $this->markTestSkipped('Redis not available: ' . $e->getMessage());
        }
    }
    
    public function testStoreAndRetrieveSession(): void
    {
        $token = 'test_token_' . uniqid();
        $userData = [
            'userId' => 999,
            'email' => 'integration@test.com',
            'roles' => ['ROLE_USER']
        ];
        
        // Сохраняем сессию
        $this->sessionManager->storeUserSession($token, $userData);
        
        // Получаем обратно
        $retrieved = $this->sessionManager->getUserSession($token);
        
        $this->assertNotNull($retrieved);
        $this->assertEquals($userData['userId'], $retrieved['userId']);
        $this->assertEquals($userData['email'], $retrieved['email']);
    }
    
    public function testRemoveSession(): void
    {
        $token = 'test_token_' . uniqid();
        $userData = ['userId' => 888, 'email' => 'remove@test.com'];
        
        $this->sessionManager->storeUserSession($token, $userData);
        
        // Убеждаемся что сессия сохранена
        $this->assertNotNull($this->sessionManager->getUserSession($token));
        
        // Удаляем
        $this->sessionManager->removeUserSession($token);
        
        // Проверяем что удалена
        $this->assertNull($this->sessionManager->getUserSession($token));
    }
    
    public function testCacheOperations(): void
    {
        $cacheKey = 'test_cache_' . uniqid();
        $testData = ['data' => 'test', 'number' => 42];
        
        // Сохраняем в кэш
        $this->sessionManager->cacheGetRequest($cacheKey, $testData, 10);
        
        // Получаем из кэша
        $cached = $this->sessionManager->getCachedRequest($cacheKey);
        
        $this->assertEquals($testData, $cached);
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->sessionManager = null;
    }
}