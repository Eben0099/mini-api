<?php
// src/Repository/BookingRepository.php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Stylist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function findOverlappingBookings(Stylist $stylist, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt, ?int $excludeBookingId = null): array
    {
        $qb = $this->createQueryBuilder('b')
            ->andWhere('b.stylist = :stylist')
            ->setParameter('stylist', $stylist)
            ->andWhere('b.status IN (:activeStatuses)')
            ->setParameter('activeStatuses', [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED])
            ->andWhere('(b.startAt < :endAt AND b.endAt > :startAt)')
            ->setParameter('startAt', $startAt)
            ->setParameter('endAt', $endAt);

        if ($excludeBookingId !== null) {
            $qb->andWhere('b.id != :excludeId')
                ->setParameter('excludeId', $excludeBookingId);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByClientId(int $clientId): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.salon', 'salon')
            ->leftJoin('b.stylist', 'stylist')
            ->leftJoin('b.service', 'service')
            ->addSelect('salon', 'stylist', 'service')
            ->andWhere('b.client = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('b.startAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findBySalonId(int $salonId, \DateTimeImmutable $startDate = null, \DateTimeImmutable $endDate = null): array
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.client', 'client')
            ->leftJoin('b.stylist', 'stylist')
            ->leftJoin('b.service', 'service')
            ->addSelect('client', 'stylist', 'service')
            ->andWhere('b.salon = :salonId')
            ->setParameter('salonId', $salonId)
            ->orderBy('b.startAt', 'ASC');

        if ($startDate) {
            $qb->andWhere('b.startAt >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('b.endAt <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->getQuery()->getResult();
    }

    public function findUpcomingBookings(int $clientId): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('b')
            ->leftJoin('b.salon', 'salon')
            ->leftJoin('b.stylist', 'stylist')
            ->leftJoin('b.service', 'service')
            ->addSelect('salon', 'stylist', 'service')
            ->andWhere('b.client = :clientId')
            ->setParameter('clientId', $clientId)
            ->andWhere('b.startAt > :now')
            ->setParameter('now', $now)
            ->andWhere('b.status IN (:activeStatuses)')
            ->setParameter('activeStatuses', [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED])
            ->orderBy('b.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPastBookingsForReview(int $clientId): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('b')
            ->leftJoin('b.review', 'review')
            ->addSelect('review')
            ->andWhere('b.client = :clientId')
            ->setParameter('clientId', $clientId)
            ->andWhere('b.endAt < :now')
            ->setParameter('now', $now)
            ->andWhere('b.status = :confirmed')
            ->setParameter('confirmed', Booking::STATUS_CONFIRMED)
            ->andWhere('review.id IS NULL')
            ->orderBy('b.endAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}