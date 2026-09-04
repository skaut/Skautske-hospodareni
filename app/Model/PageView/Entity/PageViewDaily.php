<?php

declare(strict_types=1);

namespace App\Model\PageView\Entity;

use App\Model\Infrastructure\Entity\AbstractIdEntity;
use App\Model\PageView\Repository\PageViewDailyRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * How many times one page was shown on one day.
 *
 * The point of the table is what is not in it: no user, no unit, no IP address
 * and no session, so it answers which parts of the application are worth the
 * work and can say nothing about a person. That is also why it replaces the
 * external analytics the layout used to load.
 *
 * Rows are written by an upsert in {@see PageViewDailyRepository::increment()},
 * never through the entity manager — a counter has no other operation.
 */
#[Entity(repositoryClass: PageViewDailyRepository::class)]
#[Table(name: 'page_view_daily')]
#[UniqueConstraint(name: 'page_view_daily_page_day_uniq', columns: ['page_key', 'day'])]
#[Index(name: 'page_view_daily_day_idx', columns: ['day'])]
class PageViewDaily extends AbstractIdEntity
{
    /** The same limit `page_help` uses, so both address a page by the same key. */
    public const PAGE_KEY_MAX_LENGTH = 191;

    #[Column(name: 'page_key', type: Types::STRING, length: self::PAGE_KEY_MAX_LENGTH)]
    private string $pageKey;

    #[Column(name: 'day', type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $day;

    #[Column(name: 'views', type: Types::INTEGER, options: ['unsigned' => true])]
    private int $views;

    public function __construct(string $pageKey, DateTimeImmutable $day, int $views = 1)
    {
        $this->pageKey = $pageKey;
        $this->day = $day;
        $this->views = $views;
    }

    public function getPageKey(): string
    {
        return $this->pageKey;
    }

    public function getDay(): DateTimeImmutable
    {
        return $this->day;
    }

    public function getViews(): int
    {
        return $this->views;
    }
}
