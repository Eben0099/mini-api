<?php
// src/Repository/ReviewRepository.php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function findApprovedByStylistId(int $stylistId): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.client', 'client')
            ->addSelect('client')
            ->andWhere('r.stylist = :stylistId')
            ->setParameter('stylistId', $stylistId)
            ->andWhere('r.status = :approved')
            ->setParameter('approved', Review::STATUS_APPROVED)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findBySalonId(int $salonId): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.stylist', 'stylist')
            ->leftJoin('stylist.salon', 'salon')
            ->leftJoin('r.client', 'client')
            ->addSelect('stylist', 'client')
            ->andWhere('salon.id = :salonId')
            ->setParameter('salonId', $salonId)
            ->andWhere('r.status = :approved')
            ->setParameter('approved', Review::STATUS_APPROVED)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingReviews(int $salonId = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.stylist', 'stylist')
            ->leftJoin('stylist.salon', 'salon')
            ->leftJoin('r.client', 'client')
            ->addSelect('stylist', 'salon', 'client')
            ->andWhere('r.status = :pending')
            ->setParameter('pending', Review::STATUS_PENDING)
            ->orderBy('r.createdAt', 'ASC');

        if ($salonId !== null) {
            $qb->andWhere('salon.id = :salonId')
                ->setParameter('salonId', $salonId);
        }

        return $qb->getQuery()->getResult();
    }

    public function getAverageRatingByStylistId(int $stylistId): float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) as averageRating')
            ->andWhere('r.stylist = :stylistId')
            ->setParameter('stylistId', $stylistId)
            ->andWhere('r.status = :approved')
            ->setParameter('approved', Review::STATUS_APPROVED)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    public function getReviewCountByStylistId(int $stylistId): int
    {
        return $this->count([
            'stylist' => $stylistId,
            'status' => Review::STATUS_APPROVED
        ]);
    }

    public function findOneByBookingId(int $bookingId): ?Review
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.booking = :bookingId')
            ->setParameter('bookingId', $bookingId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}