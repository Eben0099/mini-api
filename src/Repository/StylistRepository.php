<?php
// src/Repository/StylistRepository.php

namespace App\Repository;

use App\Entity\Stylist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StylistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stylist::class);
    }

    public function findBySalonId(int $salonId): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.user', 'user')
            ->addSelect('user')
            ->andWhere('s.salon = :salonId')
            ->setParameter('salonId', $salonId)
            ->orderBy('user.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByLanguage(string $language): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.user', 'user')
            ->addSelect('user')
            ->andWhere('s.languages LIKE :language')
            ->setParameter('language', '%' . $language . '%')
            ->getQuery()
            ->getResult();
    }

    public function findAvailableStylists(int $salonId, array $serviceIds = []): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.user', 'user')
            ->addSelect('user')
            ->andWhere('s.salon = :salonId')
            ->setParameter('salonId', $salonId);

        if (!empty($serviceIds)) {
            $qb->andWhere('s.skills LIKE :serviceIds')
                ->setParameter('serviceIds', '%' . implode(',', $serviceIds) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function findWithPortfolio(int $stylistId): ?Stylist
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.media', 'media')
            ->addSelect('media')
            ->andWhere('s.id = :stylistId')
            ->setParameter('stylistId', $stylistId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}