<?php
// tests/Unit/Entity/BookingTest.php
namespace App\Tests\Unit\Entity;

use App\Entity\Booking;
use PHPUnit\Framework\TestCase;

class BookingTest extends TestCase
{
    public function testBookingCreation(): void
    {
        $booking = new Booking();
        $startTime = new \DateTime('2024-01-01 10:00:00');
        $endTime = new \DateTime('2024-01-01 12:00:00');
        
        $booking->setStartTime($startTime);
        $booking->setEndTime($endTime);
        
        $this->assertEquals($startTime, $booking->getStartTime());
        $this->assertEquals($endTime, $booking->getEndTime());
    }
    
    public function testBookingStatus(): void
    {
        $booking = new Booking();
        
        $booking->setStatus('confirmed');
        $this->assertEquals('confirmed', $booking->getStatus());
        
        $booking->setStatus('pending');
        $this->assertEquals('pending', $booking->getStatus());
        
        $booking->setStatus('cancelled');
        $this->assertEquals('cancelled', $booking->getStatus());
    }
    
    public function testInvalidStatusThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $booking = new Booking();
        $booking->setStatus('invalid_status');
    }
}