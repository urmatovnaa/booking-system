<?php
namespace App\Tests\Unit\Message;

use App\Message\ResourceCreatedMessage;
use PHPUnit\Framework\TestCase;

class ResourceCreatedMessageTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $resourceId = 99;
        $name = "Conference Room A";
        
        $message = new ResourceCreatedMessage($resourceId, $name);
        
        $this->assertEquals($resourceId, $message->getResourceId());
        $this->assertEquals($name, $message->getName());
    }
    
    public function testMessageImmutability(): void
    {
        $message = new ResourceCreatedMessage(1, "Test Resource");
        
        // Проверяем наличие геттеров
        $this->assertTrue(method_exists($message, 'getResourceId'));
        $this->assertTrue(method_exists($message, 'getName'));
        
        // Проверяем отсутствие сеттеров
        $this->assertFalse(method_exists($message, 'setResourceId'));
        $this->assertFalse(method_exists($message, 'setName'));
    }
}