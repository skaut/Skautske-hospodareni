<?php

declare(strict_types=1);

namespace App\Model\Announcement\Manager;

use App\Model\Announcement\Entity\Announcement;
use App\Model\Announcement\Entity\AnnouncementCategory;
use App\Model\Announcement\Enum\AnnouncementCategoryCode;
use App\Model\Infrastructure\Manager\AbstractManager;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

final class AnnouncementManager extends AbstractManager
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return Announcement::class;
    }

    public function create(
        string $title,
        string $message,
        AnnouncementCategoryCode $categoryCode,
        ?DateTimeImmutable $expiresAt,
    ): Announcement {
        $category = $this->getCategory($categoryCode);
        $announcement = new Announcement($title, $message, $category, $expiresAt);
        $this->em->persist($announcement);
        $this->em->flush();

        return $announcement;
    }

    public function update(
        Announcement $announcement,
        string $title,
        string $message,
        AnnouncementCategoryCode $categoryCode,
        ?DateTimeImmutable $expiresAt,
    ): void {
        $announcement->update($title, $message, $this->getCategory($categoryCode), $expiresAt);
        $this->em->persist($announcement);
        $this->em->flush();
    }

    public function setHidden(Announcement $announcement, bool $hidden): void
    {
        $announcement->setHidden($hidden);
        $this->em->persist($announcement);
        $this->em->flush();
    }

    public function delete(Announcement $announcement): void
    {
        $this->em->remove($announcement);
        $this->em->flush();
    }

    private function getCategory(AnnouncementCategoryCode $categoryCode): AnnouncementCategory
    {
        $category = $this->em->find(AnnouncementCategory::class, $categoryCode->value);
        if (! $category instanceof AnnouncementCategory) {
            throw new InvalidArgumentException('Announcement category is not available: '.$categoryCode->value);
        }

        return $category;
    }
}
