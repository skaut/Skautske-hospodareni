<?php

declare(strict_types=1);

namespace Integration\Announcement;

use App\Model\Announcement\Entity\Announcement;
use App\Model\Announcement\Entity\AnnouncementCategory;
use App\Model\Announcement\Enum\AnnouncementCategoryCode;
use App\Model\Announcement\Manager\AnnouncementManager;
use App\Model\Announcement\Repository\AnnouncementRepository;
use DateTimeImmutable;
use IntegrationTest;

final class AnnouncementRepositoryTest extends IntegrationTest
{
    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [Announcement::class];
    }

    public function testVisibleAnnouncementsAreFilteredSortedAndLimited(): void
    {
        $category = $this->createCategory();
        $now = new DateTimeImmutable('2026-09-23 12:00:00');
        $newest = new Announcement('Nejnovější', 'Text', $category, new DateTimeImmutable('2026-09-24'), new DateTimeImmutable('2026-09-23 11:00:00'));
        $middle = new Announcement('Prostřední', 'Text', $category, new DateTimeImmutable('2026-09-24'), new DateTimeImmutable('2026-09-23 10:00:00'));
        $oldest = new Announcement('Nejstarší', 'Text', $category, new DateTimeImmutable('2026-09-24'), new DateTimeImmutable('2026-09-23 09:00:00'));
        $hidden = new Announcement('Skryté', 'Text', $category, new DateTimeImmutable('2026-09-24'), new DateTimeImmutable('2026-09-23 12:00:00'));
        $hidden->setHidden(true);
        $expired = new Announcement('Prošlé', 'Text', $category, new DateTimeImmutable('2026-09-23 12:00:00'), new DateTimeImmutable('2026-09-23 12:00:00'));
        $scheduled = new Announcement('Budoucí', 'Text', $category, new DateTimeImmutable('2026-09-24'), new DateTimeImmutable('2026-09-23 13:00:00'));

        foreach ([$oldest, $newest, $middle, $hidden, $expired, $scheduled] as $announcement) {
            $this->entityManager->persist($announcement);
        }
        $this->entityManager->flush();

        $repository = new AnnouncementRepository($this->entityManager);
        $visible = $repository->findVisible($now);

        self::assertSame([$newest, $middle, $oldest], $visible);
        self::assertSame([$newest, $middle], $repository->findVisible($now, 2));
    }

    public function testVisibleAnnouncementsIncludeThoseWithoutExpiration(): void
    {
        $category = $this->createCategory();
        $announcement = new Announcement(
            'Bez expirace',
            'Text',
            $category,
            null,
            new DateTimeImmutable('2026-09-23 11:00:00'),
        );
        $this->entityManager->persist($announcement);
        $this->entityManager->flush();

        $repository = new AnnouncementRepository($this->entityManager);
        self::assertSame([$announcement], $repository->findVisible(new DateTimeImmutable('2026-09-23 12:00:00')));
    }

    public function testManagerSupportsCreateHideRestoreAndPermanentDelete(): void
    {
        $this->createCategory();
        $manager = new AnnouncementManager($this->entityManager);
        $repository = new AnnouncementRepository($this->entityManager);

        $announcement = $manager->create('Název', 'Text', AnnouncementCategoryCode::NEWS, null);
        self::assertNotNull($announcement->getId());

        $manager->setHidden($announcement, true);
        self::assertTrue($announcement->isHidden());
        $manager->setHidden($announcement, false);
        self::assertFalse($announcement->isHidden());

        $id = $announcement->getId();
        $manager->delete($announcement);
        self::assertNull($repository->find($id));
    }

    private function createCategory(): AnnouncementCategory
    {
        $category = new AnnouncementCategory(AnnouncementCategoryCode::NEWS);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }
}
