<?php

declare(strict_types=1);

namespace App\Model\PageView\Repository;

use App\Model\PageView\Entity\PageViewDaily;
use DateTimeImmutable;
use IntegrationTest;

class PageViewDailyRepositoryTest extends IntegrationTest
{
    private PageViewDailyRepository $repository;

    /** @return string[] */
    public function getTestedAggregateRoots(): array
    {
        return [PageViewDaily::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);

        parent::_before();

        $this->repository = new PageViewDailyRepository($this->entityManager);
    }

    public function testIncrementKeepsOneRowPerPageAndDay(): void
    {
        $day = new DateTimeImmutable('2026-08-28');

        $this->repository->increment('Travel:Contract:default', $day);
        $this->repository->increment('Travel:Contract:default', $day);
        $this->repository->increment('Travel:Contract:default', $day);

        self::assertSame(1, (int) $this->tester->grabNumRecords('page_view_daily'));

        /** @var PageViewDaily[] $rows */
        $rows = $this->repository->findAll();

        // Also proves the upsert writes the same columns the mapping describes.
        self::assertSame('Travel:Contract:default', $rows[0]->getPageKey());
        self::assertSame('2026-08-28', $rows[0]->getDay()->format('Y-m-d'));
        self::assertSame(3, $rows[0]->getViews());
    }

    public function testIncrementStartsANewRowOnTheNextDay(): void
    {
        $this->repository->increment('Payments:Payment:default', new DateTimeImmutable('2026-08-28'));
        $this->repository->increment('Payments:Payment:default', new DateTimeImmutable('2026-08-29'));

        self::assertSame(2, (int) $this->tester->grabNumRecords('page_view_daily'));
        self::assertSame(
            ['Payments:Payment:default' => 2],
            $this->repository->sumByPage(new DateTimeImmutable('2026-08-01'), new DateTimeImmutable('2026-08-31')),
        );
    }

    public function testSumByPageCountsOnlyDaysInsideTheRange(): void
    {
        $this->repository->increment('Unit:Cashbook:default', new DateTimeImmutable('2026-07-31'));
        $this->repository->increment('Unit:Cashbook:default', new DateTimeImmutable('2026-08-01'));
        $this->repository->increment('Unit:Cashbook:default', new DateTimeImmutable('2026-08-31'));
        $this->repository->increment('Unit:Cashbook:default', new DateTimeImmutable('2026-09-01'));

        self::assertSame(
            ['Unit:Cashbook:default' => 2],
            $this->repository->sumByPage(new DateTimeImmutable('2026-08-01'), new DateTimeImmutable('2026-08-31')),
        );
    }

    public function testSumByPageReturnsMostUsedPagesFirst(): void
    {
        $day = new DateTimeImmutable('2026-08-28');

        $this->repository->increment('Camps:Default:default', $day);
        $this->repository->increment('Events:Event:default', $day);
        $this->repository->increment('Events:Event:default', $day);

        self::assertSame(
            ['Events:Event:default' => 2, 'Camps:Default:default' => 1],
            $this->repository->sumByPage($day, $day),
        );
    }

    public function testFindFirstDayReportsTheOldestCountedDay(): void
    {
        self::assertNull($this->repository->findFirstDay());

        $this->repository->increment('Dashboard:default', new DateTimeImmutable('2026-08-28'));
        $this->repository->increment('Dashboard:default', new DateTimeImmutable('2026-07-14'));

        self::assertSame('2026-07-14', $this->repository->findFirstDay()?->format('Y-m-d'));
    }
}
