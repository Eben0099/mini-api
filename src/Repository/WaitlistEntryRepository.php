<?php
// src/Repository/WaitlistEntryRepository.php

namespace App\Repository;

use App\Entity\WaitlistEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WaitlistEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WaitlistEntry::class);
    }

    public function findBySalonId(int $salonId): array
    {
        return $this->createQueryBuilder('w')
            ->leftJoin('w.client', 'client')
            ->leftJoin('w.service', 'service')
            ->addSelect('client', 'service')
            ->andWhere('w.salon = :salonId')
            ->setParameter('salonId', $salonId)
            ->orderBy('w.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByClientId(int $clientId): array
    {
        return $this->createQueryBuilder('w')
            ->leftJoin('w.salon', 'salon')
            ->leftJoin('w.service', 'service')
            ->addSelect('salon', 'service')
            ->andWhere('w.client = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findEntriesForTimeSlot(int $salonId, int $serviceId, \DateTimeImmutable $startTime, \DateTimeImmutable $endTime): array
    {
        return $this->createQueryBuilder('w')
            ->leftJoin('w.client', 'client')
            ->addSelect('client')
            ->andWhere('w.salon = :salonId')
            ->setParameter('salonId', $salonId)
            ->andWhere('w.service = :serviceId')
            ->setParameter('serviceId', $serviceId)
            ->andWhere('w.desiredStartRangeStart <= :endTime')
            ->setParameter('endTime', $endTime)
            ->andWhere('w.desiredStartRangeEnd >= :startTime')
            ->setParameter('startTime', $startTime)
            ->orderBy('w.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOldEntries(\DateTimeImmutable $thresholdDate): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.createdAt < :threshold')
            ->setParameter('threshold', $thresholdDate)
            ->getQuery()
            ->getResult();
    }

    public function getWaitlistCountBySalon(int $salonId): int
    {
        return $this->count([
            'salon' => $salonId
        ]);
    }
}