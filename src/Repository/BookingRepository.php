<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Resource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry \$registry)
    {
        parent::__construct(\$registry, Booking::class);
    }

    public function findOverlappingBooking(Resource \$resource, \DateTimeInterface \$startTime, \DateTimeInterface \$endTime, ?int \$excludeBookingId = null): ?Booking
    {
        \$qb = \$this->createQueryBuilder(b)
            ->where(b.resource = :resource)
            ->andWhere(b.status != :cancelled)
            ->andWhere((
                (b.startTime <= :startTime AND b.endTime > :startTime) OR
                (b.startTime < :endTime AND b.endTime >= :endTime) OR
                (b.startTime >= :startTime AND b.endTime <= :endTime)
            ))
            ->setParameter(resource, \$resource)
            ->setParameter(startTime, \$startTime)
            ->setParameter(endTime, \$endTime)
            ->setParameter(cancelled, cancelled)
            ->setMaxResults(1);

        if (\$excludeBookingId) {
            \$qb->andWhere(b.id != :excludeId)
               ->setParameter(excludeId, \$excludeBookingId);
        }

        return \$qb->getQuery()->getOneOrNullResult();
    }
}
