<?php
// src/Repository/AvailabilityExceptionRepository.php

namespace App\Repository;

use App\Entity\AvailabilityException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AvailabilityExceptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvailabilityException::class);
    }

    public function findSalonExceptions(int $salonId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('ae')
            ->andWhere('ae.salon = :salonId')
            ->setParameter('salonId', $salonId)
            ->andWhere('ae.date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }

    public function findStylistExceptions(int $stylistId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('ae')
            ->andWhere('ae.stylist = :stylistId')
            ->setParameter('stylistId', $stylistId)
            ->andWhere('ae.date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }

    public function findClosedDays(int $salonId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('ae')
            ->andWhere('ae.salon = :salonId OR ae.stylist IN (SELECT s.id FROM App\Entity\Stylist s WHERE s.salon = :salonId)')
            ->setParameter('salonId', $salonId)
            ->andWhere('ae.date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->andWhere('ae.closed = true')
            ->getQuery()
            ->getResult();
    }

    public function findExceptionsForDate(\DateTimeInterface $date, int $salonId = null, int $stylistId = null): array
    {
        $qb = $this->createQueryBuilder('ae')
            ->andWhere('ae.date = :date')
            ->setParameter('date', $date);

        if ($salonId !== null) {
            $qb->andWhere('ae.salon = :salonId OR ae.stylist IN (SELECT s.id FROM App\Entity\Stylist s WHERE s.salon = :salonId)')
                ->setParameter('salonId', $salonId);
        }

        if ($stylistId !== null) {
            $qb->andWhere('ae.stylist = :stylistId')
                ->setParameter('stylistId', $stylistId);
        }

        return $qb->getQuery()->getResult();
    }
}