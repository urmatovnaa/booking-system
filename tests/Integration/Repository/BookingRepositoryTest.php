<?php
// tests/Integration/Repository/BookingRepositoryTest.php
namespace App\Tests\Integration\Repository;

use App\Entity\Booking;
use App\Entity\User;
use App\Entity\Resource;

class BookingRepositoryTest extends BaseRepositoryTest
{
    private $user;
    private $resource;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем тестовые данные
        $this->createTestData();
    }
    
    private function createTestData(): void
    {
        // Создаем пользователя
        $this->user = new User();
        $this->user->setEmail('booking-test@example.com');
        $this->user->setPassword('password123');
        
        if (method_exists($this->user, 'setRoles')) {
            $this->user->setRoles(['ROLE_USER']);
        }
        
        $this->entityManager->persist($this->user);
        
        // Создаем ресурс
        $this->resource = new Resource();
        $this->resource->setName('Meeting Room A');
        
        // Проверяем и устанавливаем дополнительные поля если они существуют
        if (method_exists($this->resource, 'setDescription')) {
            $this->resource->setDescription('Test meeting room');
        }
        
        if (method_exists($this->resource, 'setStatus')) {
            $this->resource->setStatus('active');
        }
        
        // УСТАНАВЛИВАЕМ ПОЛЬЗОВАТЕЛЯ ДЛЯ РЕСУРСА - ВАЖНО!
        $this->resource->setUser($this->user);
        
        $this->entityManager->persist($this->resource);
        $this->entityManager->flush();
        $this->entityManager->clear();
    }
    
    public function testRepositoryBasicOperations(): void
    {
        $repository = $this->entityManager->getRepository(Booking::class);
        
        // findAll() должен возвращать массив
        $allBookings = $repository->findAll();
        $this->assertIsArray($allBookings);
        
        // Перезагружаем сущности из базы
        $userRepository = $this->entityManager->getRepository(User::class);
        $resourceRepository = $this->entityManager->getRepository(Resource::class);
        
        $user = $userRepository->findOneBy(['email' => 'booking-test@example.com']);
        $resource = $resourceRepository->findOneBy(['name' => 'Meeting Room A']);
        
        if (!$user || !$resource) {
            $this->fail('Test data not found');
            return;
        }
        
        // Проверяем что можем создать и найти бронирование
        $booking = new Booking();
        $booking->setUser($user);
        $booking->setResource($resource);
        $booking->setStartTime(new \DateTime('2024-01-02 10:00:00'));
        $booking->setEndTime(new \DateTime('2024-01-02 12:00:00'));
        
        if (method_exists($booking, 'setStatus')) {
            $booking->setStatus('confirmed');
        }
        
        $this->entityManager->persist($booking);
        $this->entityManager->flush();
        
        $foundBooking = $repository->find($booking->getId());
        $this->assertNotNull($foundBooking);
        $this->assertEquals($booking->getId(), $foundBooking->getId());
        
        // Проверяем что можем найти бронирование по пользователю
        $userBookings = $repository->findBy(['user' => $user]);
        $this->assertCount(1, $userBookings);
        
        // Проверяем что можем найти бронирование по ресурсу
        $resourceBookings = $repository->findBy(['resource' => $resource]);
        $this->assertCount(1, $resourceBookings);
        
        // Убрали echo: echo "✅ BookingRepository basic operations work\n";
    }
    
    public function testFindOverlappingBookingDetectsConflict(): void
    {
        // Проверяем существует ли метод findOverlappingBooking
        $repository = $this->entityManager->getRepository(Booking::class);
        
        if (!method_exists($repository, 'findOverlappingBooking')) {
            $this->markTestSkipped('Repository method findOverlappingBooking not found');
            return;
        }
        
        // Перезагружаем сущности из базы
        $userRepository = $this->entityManager->getRepository(User::class);
        $resourceRepository = $this->entityManager->getRepository(Resource::class);
        
        $user = $userRepository->findOneBy(['email' => 'booking-test@example.com']);
        $resource = $resourceRepository->findOneBy(['name' => 'Meeting Room A']);
        
        if (!$user || !$resource) {
            $this->fail('Test data not found');
            return;
        }
        
        // Создаем первое бронирование
        $booking1 = new Booking();
        $booking1->setUser($user);
        $booking1->setResource($resource);
        $booking1->setStartTime(new \DateTime('2024-01-01 10:00:00'));
        $booking1->setEndTime(new \DateTime('2024-01-01 12:00:00'));
        
        if (method_exists($booking1, 'setStatus')) {
            $booking1->setStatus('confirmed');
        }
        
        $this->entityManager->persist($booking1);
        $this->entityManager->flush();
        $this->entityManager->clear();
        
        // Перезагружаем ресурс
        $resource = $resourceRepository->findOneBy(['name' => 'Meeting Room A']);
        
        if (!$resource) {
            $this->fail('Failed to reload resource');
            return;
        }
        
        // Проверяем перекрывающееся бронирование
        $overlapping = $repository->findOverlappingBooking(
            $resource,
            new \DateTime('2024-01-01 11:00:00'),
            new \DateTime('2024-01-01 13:00:00')
        );
        
        $this->assertNotNull($overlapping);
        $this->assertEquals('2024-01-01 10:00:00', $overlapping->getStartTime()->format('Y-m-d H:i:s'));
        
        // Убрали echo: echo "✅ findOverlappingBooking detects conflicts\n";
    }
    
    public function testFindOverlappingBookingReturnsNullWhenNoConflict(): void
    {
        // Проверяем существует ли метод findOverlappingBooking
        $repository = $this->entityManager->getRepository(Booking::class);
        
        if (!method_exists($repository, 'findOverlappingBooking')) {
            $this->markTestSkipped('Repository method findOverlappingBooking not found');
            return;
        }
        
        // Перезагружаем сущности из базы
        $userRepository = $this->entityManager->getRepository(User::class);
        $resourceRepository = $this->entityManager->getRepository(Resource::class);
        
        $user = $userRepository->findOneBy(['email' => 'booking-test@example.com']);
        $resource = $resourceRepository->findOneBy(['name' => 'Meeting Room A']);
        
        if (!$user || !$resource) {
            $this->fail('Test data not found');
            return;
        }
        
        // Создаем бронирование
        $booking1 = new Booking();
        $booking1->setUser($user);
        $booking1->setResource($resource);
        $booking1->setStartTime(new \DateTime('2024-01-01 10:00:00'));
        $booking1->setEndTime(new \DateTime('2024-01-01 12:00:00'));
        
        if (method_exists($booking1, 'setStatus')) {
            $booking1->setStatus('confirmed');
        }
        
        $this->entityManager->persist($booking1);
        $this->entityManager->flush();
        $this->entityManager->clear();
        
        // Перезагружаем ресурс
        $resource = $resourceRepository->findOneBy(['name' => 'Meeting Room A']);
        
        if (!$resource) {
            $this->fail('Failed to reload resource');
            return;
        }
        
        // Проверяем НЕ перекрывающееся бронирование
        $overlapping = $repository->findOverlappingBooking(
            $resource,
            new \DateTime('2024-01-01 14:00:00'),
            new \DateTime('2024-01-01 16:00:00')
        );
        
        $this->assertNull($overlapping);
        
        // Убрали echo: echo "✅ findOverlappingBooking returns null when no conflict\n";
    }
}