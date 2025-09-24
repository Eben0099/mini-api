<?php
// src/Repository/StatisticsRepository.php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Salon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StatisticsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function getSalonStatistics(int $salonId, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('b')
            ->select([
                'COUNT(b.id) as totalBookings',
                'SUM(CASE WHEN b.status = :confirmed THEN 1 ELSE 0 END) as confirmedBookings',
                'SUM(CASE WHEN b.status = :cancelled THEN 1 ELSE 0 END) as cancelledBookings',
                'AVG(CASE WHEN r.status = :approved THEN r.rating ELSE NULL END) as averageRating'
            ])
            ->leftJoin('b.review', 'r')
            ->andWhere('b.salon = :salonId')
            ->andWhere('b.startAt BETWEEN :startDate AND :endDate')
            ->setParameter('salonId', $salonId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('confirmed', Booking::STATUS_CONFIRMED)
            ->setParameter('cancelled', Booking::STATUS_CANCELLED)
            ->setParameter('approved', 'APPROVED')
            ->getQuery()
            ->getSingleResult();
    }

    public function getMonthlyRevenue(int $salonId, int $year): array
    {
        $results = $this->createQueryBuilder('b')
            ->select('MONTH(b.startAt) as month, SUM(s.priceCents) as revenue')
            ->leftJoin('b.service', 's')
            ->andWhere('b.salon = :salonId')
            ->andWhere('YEAR(b.startAt) = :year')
            ->andWhere('b.status = :confirmed')
            ->setParameter('salonId', $salonId)
            ->setParameter('year', $year)
            ->setParameter('confirmed', Booking::STATUS_CONFIRMED)
            ->groupBy('month')
            ->getQuery()
            ->getResult();

        // Format results to include all months
        $monthlyRevenue = array_fill(1, 12, 0);
        foreach ($results as $result) {
            $monthlyRevenue[$result['month']] = (int) $result['revenue'];
        }

        return $monthlyRevenue;
    }
}