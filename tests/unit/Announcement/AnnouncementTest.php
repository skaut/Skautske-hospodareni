<?php

declare(strict_types=1);

namespace Unit\Announcement;

use App\Model\Announcement\Entity\Announcement;
use App\Model\Announcement\Entity\AnnouncementCategory;
use App\Model\Announcement\Enum\AnnouncementCategoryCode;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AnnouncementTest extends TestCase
{
    public function testAcceptsMessageOf600Characters(): void
    {
        $announcement = new Announcement(
            'Test',
            str_repeat('x', Announcement::MAX_MESSAGE_LENGTH),
            new AnnouncementCategory(AnnouncementCategoryCode::NEWS),
            new DateTimeImmutable('+1 day'),
        );

        self::assertSame(600, strlen($announcement->getMessage()));
    }

    public function testExpirationCanBeOmittedAndClearedOnUpdate(): void
    {
        $category = new AnnouncementCategory(AnnouncementCategoryCode::NEWS);
        $announcement = new Announcement('Test', 'Text', $category, null);

        self::assertNull($announcement->getExpiresAt());

        $announcement->update('Upraveno', 'Nový text', $category, new DateTimeImmutable('+1 day'));
        self::assertNotNull($announcement->getExpiresAt());

        $announcement->update('Upraveno', 'Nový text', $category, null);
        self::assertNull($announcement->getExpiresAt());
    }

    public function testRejectsMessageLongerThan600Characters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Announcement(
            'Test',
            str_repeat('x', Announcement::MAX_MESSAGE_LENGTH + 1),
            new AnnouncementCategory(AnnouncementCategoryCode::NEWS),
            new DateTimeImmutable('+1 day'),
        );
    }

    public function testUpdateChangesContentWithoutChangingPublicationDate(): void
    {
        $publishedAt = new DateTimeImmutable('2026-01-01 12:00:00');
        $category = new AnnouncementCategory(AnnouncementCategoryCode::NEWS);
        $announcement = new Announcement('Původní název', 'Původní text', $category, new DateTimeImmutable('+1 day'), $publishedAt);
        $newCategory = new AnnouncementCategory(AnnouncementCategoryCode::WARNING);

        $announcement->update('Nový název', 'Nový text', $newCategory, new DateTimeImmutable('+2 days'));

        self::assertSame('Nový název', $announcement->getTitle());
        self::assertSame('Nový text', $announcement->getMessage());
        self::assertSame(AnnouncementCategoryCode::WARNING, $announcement->getCategory()->getCode());
        self::assertSame($publishedAt, $announcement->getPublishedAt());
    }
}
