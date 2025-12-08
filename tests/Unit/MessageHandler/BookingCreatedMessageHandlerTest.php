<?php
namespace App\Tests\Unit\MessageHandler;

use App\Message\BookingCreatedMessage;
use App\MessageHandler\BookingCreatedMessageHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BookingCreatedMessageHandlerTest extends TestCase
{
    public function testHandlerLogsBookingCreation(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        
        $loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Новая бронь создана:(пользователь: 50, (ресурс:25) (ID: 100)'));
        
        $handler = new BookingCreatedMessageHandler($loggerMock);
        $message = new BookingCreatedMessage(100, 50, 25);
        
        $handler->__invoke($message);
    }
    
    public function testHandlerWithDifferentData(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        
        $loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('ID: 999'));
        
        $handler = new BookingCreatedMessageHandler($loggerMock);
        $message = new BookingCreatedMessage(999, 888, 777);
        
        $handler->__invoke($message);
    }
}