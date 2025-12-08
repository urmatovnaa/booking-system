<?php
// tests/Integration/Repository/BaseRepositoryTest.php
namespace App\Tests\Integration\Repository;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Базовый класс для тестов репозиториев
 * 
 * @group integration
 * @group repository
 */
class BaseRepositoryTest extends KernelTestCase
{
    protected $entityManager;
    protected $schemaTool;
    
    protected function setUp(): void
    {
        self::bootKernel();
        
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        
        // Создаем схему базы данных
        $this->createSchema();
    }
    
    private function createSchema(): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        
        if (empty($metadata)) {
            throw new \RuntimeException('No metadata found for entities');
        }
        
        $this->schemaTool = new SchemaTool($this->entityManager);
        
        // Удаляем существующую схему если есть
        try {
            $this->schemaTool->dropSchema($metadata);
        } catch (\Exception $e) {
            // Игнорируем ошибки если схема не существует
        }
        
        // Используем новый метод без deprecation warning
        $sqls = $this->schemaTool->getCreateSchemaSql($metadata);
        $connection = $this->entityManager->getConnection();
        
        foreach ($sqls as $sql) {
            $connection->executeStatement($sql);
        }
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
        
        $this->schemaTool = null;
    }
    
    /**
     * Пустой тест чтобы PHPUnit не жаловался
     */
    public function testBaseClass(): void
    {
        $this->addToAssertionCount(1); // Просто увеличиваем счетчик assertion'ов
    }
}