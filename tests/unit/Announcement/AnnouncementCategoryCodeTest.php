<?php

declare(strict_types=1);

namespace Unit\Announcement;

use App\Model\Announcement\Enum\AnnouncementCategoryCode;
use PHPUnit\Framework\TestCase;

final class AnnouncementCategoryCodeTest extends TestCase
{
    public function testAllCategoriesHaveLabelsAndIcons(): void
    {
        self::assertSame('fi fi-rr-newspaper', AnnouncementCategoryCode::NEWS->icon());
        self::assertSame('fi fi-rr-circle-xmark', AnnouncementCategoryCode::ERROR->icon());
        self::assertSame(['news', 'info', 'warning', 'error', 'plan'], array_map(
            static fn (AnnouncementCategoryCode $category): string => $category->value,
            AnnouncementCategoryCode::cases(),
        ));

        $icons = [];
        foreach (AnnouncementCategoryCode::cases() as $category) {
            self::assertNotSame('', $category->label());
            self::assertStringStartsWith('fi ', $category->icon());
            $icons[] = $category->icon();
        }

        self::assertCount(count(AnnouncementCategoryCode::cases()), array_unique($icons));
    }
}
