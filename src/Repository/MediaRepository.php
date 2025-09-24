<?php
// src/Repository/MediaRepository.php

namespace App\Repository;

use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    public function findByStylistId(int $stylistId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.stylist = :stylistId')
            ->setParameter('stylistId', $stylistId)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findImagesByStylistId(int $stylistId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.stylist = :stylistId')
            ->setParameter('stylistId', $stylistId)
            ->andWhere('m.mimeType LIKE :imageType')
            ->setParameter('imageType', 'image/%')
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentMedia(int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.stylist', 'stylist')
            ->leftJoin('stylist.user', 'user')
            ->addSelect('stylist', 'user')
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findLargeFiles(int $sizeThreshold = 5242880): array // 5MB
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.sizeBytes > :threshold')
            ->setParameter('threshold', $sizeThreshold)
            ->getQuery()
            ->getResult();
    }

    public function getTotalStorageUsedByStylist(int $stylistId): int
    {
        $result = $this->createQueryBuilder('m')
            ->select('SUM(m.sizeBytes) as totalSize')
            ->andWhere('m.stylist = :stylistId')
            ->setParameter('stylistId', $stylistId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }
}