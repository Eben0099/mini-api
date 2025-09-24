<?php
// src/Repository/SalonRepository.php

namespace App\Repository;

use App\Entity\Salon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SalonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Salon::class);
    }

    public function findByFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.stylists', 'stylist')
            ->leftJoin('s.services', 'service')
            ->addSelect('stylist', 'service');

        if (!empty($filters['city'])) {
            $qb->andWhere('s.city = :city')
                ->setParameter('city', $filters['city']);
        }

        if (!empty($filters['q'])) {
            $qb->andWhere('s.name LIKE :query OR s.address LIKE :query')
                ->setParameter('query', '%' . $filters['q'] . '%');
        }

        if (!empty($filters['lang'])) {
            $qb->andWhere('stylist.languages LIKE :lang')
                ->setParameter('lang', '%' . $filters['lang'] . '%');
        }

        if (isset($filters['priceSort']) && in_array($filters['priceSort'], ['asc', 'desc'])) {
            $qb->orderBy('service.priceCents', $filters['priceSort']);
        } else {
            $qb->orderBy('s.name', 'ASC');
        }

        return $qb->getQuery()->getResult();
    }

    public function findWithAverageRating(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.stylists', 'stylist')
            ->leftJoin('stylist.reviews', 'review')
            ->andWhere('review.status = :approved')
            ->setParameter('approved', 'APPROVED')
            ->addSelect('AVG(review.rating) as avgRating')
            ->groupBy('s.id')
            ->getQuery()
            ->getResult();
    }

    public function findByOwnerId(int $ownerId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.owner = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->getQuery()
            ->getResult();
    }

    public function findOneBySlug(string $slug): ?Salon
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.services', 'service')
            ->leftJoin('s.stylists', 'stylist')
            ->leftJoin('stylist.user', 'user')
            ->addSelect('service', 'stylist', 'user')
            ->andWhere('s.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }
}