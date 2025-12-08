<?php
namespace App\Tests\Unit\Message;

use App\Message\UserRegisteredMessage;
use PHPUnit\Framework\TestCase;

class UserRegisteredMessageTest extends TestCase
{
    public function testConstructorAndProperties(): void
    {
        $userId = 123;
        $email = "test@example.com";
        
        $message = new UserRegisteredMessage($userId, $email);
        
        $this->assertEquals($userId, $message->userId);
        $this->assertEquals($email, $message->email);
    }
    
    public function testMessageHasPublicProperties(): void
    {
        $message = new UserRegisteredMessage(1, "test@example.com");
        
        // Проверяем что свойства публичные
        $this->assertEquals(1, $message->userId);
        $this->assertEquals("test@example.com", $message->email);
    }
}