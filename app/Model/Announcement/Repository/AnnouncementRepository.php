<?php

declare(strict_types=1);

namespace App\Model\Announcement\Repository;

use App\Model\Announcement\Entity\Announcement;
use App\Model\Infrastructure\Repository\AbstractRepository;
use DateTimeImmutable;

/** @extends AbstractRepository<Announcement> */
final class AnnouncementRepository extends AbstractRepository
{
    public function getEntityClass(): string
    {
        return Announcement::class;
    }

    /** @return Announcement[] */
    public function findVisible(DateTimeImmutable $at, ?int $limit = null): array
    {
        $query = $this->createQueryBuilder('announcement')
            ->addSelect('category')
            ->join('announcement.category', 'category')
            ->andWhere('announcement.hidden = false')
            ->andWhere('announcement.publishedAt <= :at')
            ->andWhere('(announcement.expiresAt IS NULL OR announcement.expiresAt > :at)')
            ->setParameter('at', $at)
            ->orderBy('announcement.publishedAt', 'DESC')
            ->addOrderBy('announcement.id', 'DESC');

        if ($limit !== null) {
            $query->setMaxResults($limit);
        }

        /** @var Announcement[] $announcements */
        $announcements = $query->getQuery()->getResult();

        return $announcements;
    }

    /** @return Announcement[] */
    public function findAllForAdministration(): array
    {
        /** @var Announcement[] $announcements */
        $announcements = $this->createQueryBuilder('announcement')
            ->addSelect('category')
            ->join('announcement.category', 'category')
            ->orderBy('announcement.publishedAt', 'DESC')
            ->addOrderBy('announcement.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $announcements;
    }
}
