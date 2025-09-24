<?php
// src/Repository/ServiceRepository.php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    public function findBySalonId(int $salonId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.salon = :salonId')
            ->setParameter('salonId', $salonId)
            ->orderBy('s.priceCents', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAvailableServices(int $salonId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.salon = :salonId')
            ->setParameter('salonId', $salonId)
            ->andWhere('s.durationMinutes > 0')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByPriceRange(int $minPrice, int $maxPrice): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.priceCents BETWEEN :minPrice AND :maxPrice')
            ->setParameter('minPrice', $minPrice)
            ->setParameter('maxPrice', $maxPrice)
            ->orderBy('s.priceCents', 'ASC')
            ->getQuery()
            ->getResult();
    }
}