<?php
// tests/Integration/Repository/EntityRelationsTest.php
namespace App\Tests\Integration\Repository;

use App\Entity\Booking;
use App\Entity\User;
use App\Entity\Resource;

class EntityRelationsTest extends BaseRepositoryTest
{
    public function testEntityRelations(): void
    {
        // Создаем пользователя
        $user = new User();
        $user->setEmail('relation-test@example.com');
        $user->setPassword('password123');
        
        if (method_exists($user, 'setRoles')) {
            $user->setRoles(['ROLE_USER']);
        }
        
        $this->entityManager->persist($user);
        
        // Создаем ресурс
        $resource = new Resource();
        $resource->setName('Test Room');
        $resource->setUser($user);
        
        if (method_exists($resource, 'setStatus')) {
            $resource->setStatus('active');
        }
        
        $this->entityManager->persist($resource);
        
        // Создаем бронирование
        $booking = new Booking();
        $booking->setUser($user);
        $booking->setResource($resource);
        $booking->setStartTime(new \DateTime('2024-01-01 10:00:00'));
        $booking->setEndTime(new \DateTime('2024-01-01 12:00:00'));
        
        if (method_exists($booking, 'setStatus')) {
            $booking->setStatus('confirmed');
        }
        
        $this->entityManager->persist($booking);
        $this->entityManager->flush();
        
        // ОЧЕНЬ ВАЖНО: Очищаем EntityManager и перезагружаем сущности
        $this->entityManager->clear();
        
        // Перезагружаем сущности из базы
        $resource = $this->entityManager->find(Resource::class, $resource->getId());
        $booking = $this->entityManager->find(Booking::class, $booking->getId());
        
        // Проверяем что все сущности созданы
        $this->assertNotNull($resource);
        $this->assertNotNull($booking);
        
        // Проверяем связь Booking -> Resource
        $this->assertEquals($resource->getId(), $booking->getResource()->getId());
        
        // Проверяем связь Resource -> Bookings (коллекция)
        $resourceBookings = $resource->getBookings();
        $this->assertCount(1, $resourceBookings, 'Resource should have 1 booking in collection');
        
        // Проверяем что в коллекции именно наше бронирование
        $firstBooking = $resourceBookings->first();
        $this->assertEquals($booking->getId(), $firstBooking->getId());
        
        // Убрали все echo
    }
    
    public function testBidirectionalRelationship(): void
    {
        // Этот тест проверяет двустороннюю связь
        
        // Создаем пользователя
        $user = new User();
        $user->setEmail('bidirectional-test@example.com');
        $user->setPassword('password123');
        
        if (method_exists($user, 'setRoles')) {
            $user->setRoles(['ROLE_USER']);
        }
        
        $this->entityManager->persist($user);
        
        // Создаем ресурс
        $resource = new Resource();
        $resource->setName('Bidirectional Test Room');
        $resource->setUser($user);
        
        if (method_exists($resource, 'setStatus')) {
            $resource->setStatus('active');
        }
        
        $this->entityManager->persist($resource);
        
        // Проверяем методы addBooking если они существуют
        $hasAddBookingMethod = method_exists($resource, 'addBooking');
        
        // Создаем бронирование
        $booking = new Booking();
        $booking->setStartTime(new \DateTime('2024-01-01 10:00:00'));
        $booking->setEndTime(new \DateTime('2024-01-01 12:00:00'));
        
        if (method_exists($booking, 'setStatus')) {
            $booking->setStatus('confirmed');
        }
        
        if ($hasAddBookingMethod) {
            // Используем addBooking для установки двусторонней связи
            $resource->addBooking($booking);
            $user->addBooking($booking);
        } else {
            // Используем стандартные сеттеры
            $booking->setResource($resource);
            $booking->setUser($user);
        }
        
        $this->entityManager->persist($booking);
        $this->entityManager->flush();
        $this->entityManager->clear();
        
        // Перезагружаем
        $resource = $this->entityManager->find(Resource::class, $resource->getId());
        $booking = $this->entityManager->find(Booking::class, $booking->getId());
        
        // Проверяем коллекцию
        $this->assertCount(1, $resource->getBookings());
        
        // Убрали echo
    }
}