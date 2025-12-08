<?php
namespace App\Tests\Unit\MessageHandler;

use App\Message\UserRegisteredMessage;
use App\MessageHandler\UserRegisteredMessageHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserRegisteredMessageHandlerTest extends TestCase
{
    public function testHandlerLogsMessage(): void
    {
        // Создаем mock для логгера
        $loggerMock = $this->createMock(LoggerInterface::class);
        
        // Ожидаем, что будет вызван метод info с правильным сообщением
        $loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Новый пользователь зарегистрирован: test@example.com (ID: 123)'));
        
        // Создаем handler с mock логгером
        $handler = new UserRegisteredMessageHandler($loggerMock);
        
        // Создаем message
        $message = new UserRegisteredMessage(123, 'test@example.com');
        
        // Вызываем handler
        $handler->__invoke($message);
    }
    
    public function testHandlerConstructor(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $handler = new UserRegisteredMessageHandler($loggerMock);
        
        // Просто проверяем что объект создается
        $this->assertInstanceOf(UserRegisteredMessageHandler::class, $handler);
    }
}