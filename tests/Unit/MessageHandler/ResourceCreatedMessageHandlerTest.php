<?php
namespace App\Tests\Unit\MessageHandler;

use App\Message\ResourceCreatedMessage;
use App\MessageHandler\ResourceCreatedMessageHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ResourceCreatedMessageHandlerTest extends TestCase
{
    public function testHandlerLogsResourceCreation(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        
        $loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Новый ресурс создан: Conference Room (ID: 42)'));
        
        $handler = new ResourceCreatedMessageHandler($loggerMock);
        $message = new ResourceCreatedMessage(42, 'Conference Room');
        
        $handler->__invoke($message);
    }
    
    public function testHandlerWithSpecialCharacters(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        
        $loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Новый ресурс создан: Тестовая комната №1 (ID: 1)'));
        
        $handler = new ResourceCreatedMessageHandler($loggerMock);
        $message = new ResourceCreatedMessage(1, 'Тестовая комната №1');
        
        $handler->__invoke($message);
    }
}