<?php
namespace App\Tests\Unit\Message;

use App\Message\BookingCreatedMessage;
use PHPUnit\Framework\TestCase;

class BookingCreatedMessageTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $bookingId = 100;
        $userId = 50;
        $resourceId = 25;
        
        $message = new BookingCreatedMessage($bookingId, $userId, $resourceId);
        
        $this->assertEquals($bookingId, $message->getBookingId());
        $this->assertEquals($userId, $message->getUserId());
        $this->assertEquals($resourceId, $message->getResourceId());
    }
    
    public function testNoSettersAvailable(): void
    {
        $message = new BookingCreatedMessage(1, 2, 3);
        
        // Проверяем что есть только геттеры
        $this->assertTrue(method_exists($message, 'getBookingId'));
        $this->assertTrue(method_exists($message, 'getUserId'));
        $this->assertTrue(method_exists($message, 'getResourceId'));
        
        // И нет сеттеров
        $this->assertFalse(method_exists($message, 'setBookingId'));
        $this->assertFalse(method_exists($message, 'setUserId'));
        $this->assertFalse(method_exists($message, 'setResourceId'));
    }
}