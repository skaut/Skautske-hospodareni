<?php

declare(strict_types=1);

namespace App\Model\Usage\ReadModel\QueryHandlers;

use App\Model\DTO\Usage\SystemUsage;
use App\Model\DTO\Usage\UnitUsage;
use App\Model\Usage\Entity\UsageSessionDaily;
use App\Model\Usage\ReadModel\Queries\SystemUsageQuery;
use App\Model\Usage\Repository\UsageRepository;
use App\Model\Usage\UsageSection;
use App\Model\Usage\UsageUnitCode;
use DateTimeImmutable;
use IntegrationTest;

use function array_map;
use function array_slice;

/**
 * Runs the upsert and the sums against a real MySQL: the unique key decides about the counters and the kraj
 * filter is a LIKE that must not match a kraj with a longer number.
 */
class SystemUsageQueryHandlerTest extends IntegrationTest
{
    private UsageRepository $repository;

    private SystemUsageQueryHandler $handler;

    /** @return string[] */
    public function getTestedAggregateRoots(): array
    {
        return [UsageSessionDaily::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);

        parent::_before();

        $this->repository = new UsageRepository($this->entityManager);
        $this->handler = new SystemUsageQueryHandler($this->repository);
    }

    public function testEmptyStorageYieldsNoData(): void
    {
        $usage = $this->usage();

        self::assertFalse($usage->hasData());
        self::assertSame([], $usage->sections);
        // Every kraj still has its row, so the overview shows where nothing happens.
        self::assertCount(14, $usage->rows);
        self::assertSame(0, $usage->rows[0]->sessions->getTotal());
        self::assertNull($usage->countedSince);
    }

    public function testIncrementAddsToOneRowPerDayUnitAndLevel(): void
    {
        $this->session('2026-03-02', '622.01', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');
        $this->session('2026-03-02', '622.01', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');

        self::assertSame(1, $this->tester->grabNumRecords('usage_session_daily', ['page' => 'Payments:GroupList:default']));
        $this->tester->seeInDatabase('usage_session_daily', ['page' => 'Payments:GroupList:default', 'sessions' => 2]);
        $this->tester->seeInDatabase('usage_session_daily', ['section' => '', 'screen' => '', 'page' => '', 'sessions' => 2]);
    }

    public function testTheWholeSystemShowsSectionsScreensAndKraje(): void
    {
        $this->session('2026-03-02', '622.01', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');
        $this->session('2026-03-03', '622.01', 'payments', 'Payments:Payment', 'Payments:Payment:default');
        $this->session('2026-03-03', '622', 'travel', 'Travel:Command', 'Travel:Command:detail');
        $this->session('2026-05-10', '611.02', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');
        $this->session('2026-05-10', UsageUnitCode::HEADQUARTERS, 'events', 'Events:Default', 'Events:Default:default');
        // Another year — outside the period.
        $this->session('2025-05-10', '611.02', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');

        $usage = $this->usage();

        self::assertSame(5, $usage->sessions->getTotal());
        self::assertSame(3, $usage->sessions->getMonth(3));
        self::assertSame(2, $usage->sessions->getMonth(5));
        self::assertSame(4, $usage->unitCount);
        self::assertSame(
            [UsageSection::PAYMENTS, UsageSection::EVENTS, UsageSection::TRAVEL],
            array_map(static fn ($section) => $section->section, $usage->sections),
        );

        $payments = $usage->sections[0];
        self::assertSame(3, $payments->sessions->getTotal());
        self::assertEqualsWithDelta(60.0, $payments->getShare(5), 0.001);
        self::assertSame(['Seznam skupin', 'Detail skupiny'], array_map(static fn ($screen) => $screen->label, $payments->screens));
        self::assertSame(2, $payments->screens[0]->sessions->getTotal());

        // Every kraj has a row, always in the order of the numbers, the headquarters first.
        self::assertSame([UsageUnitCode::HEADQUARTERS, '110', '210', '310'], array_slice($this->codes($usage), 0, 4));
        self::assertSame('810', $this->codes($usage)[14]);
        self::assertCount(15, $usage->rows);
        $kraj = $this->row($usage, '620');
        self::assertSame('620 Jihomoravský kraj TGM', $kraj->label);
        self::assertSame(3, $kraj->sessions->getTotal());
        self::assertSame(2, $kraj->get(UsageSection::PAYMENTS)->getTotal());
        self::assertSame(1, $kraj->get(UsageSection::TRAVEL)->getMonth(3));
        self::assertSame('620', $kraj->linkRegion);
        $headquarters = $this->row($usage, UsageUnitCode::HEADQUARTERS);
        self::assertFalse($headquarters->hasUnits());
        self::assertSame('Ústředí', $headquarters->label);
        self::assertEquals(new DateTimeImmutable('2025-05-10'), $usage->countedSince);
    }

    /** Switching the role does not make one login two sessions of the system, but each unit counts it. */
    public function testALoginWithTwoRolesCountsOnceInTheSystemAndUnderBothUnits(): void
    {
        $this->session('2026-03-02', '622.01', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');
        $this->session('2026-03-02', '611.02', 'payments', 'Payments:GroupList', 'Payments:GroupList:default', newLogin: false);

        $usage = $this->usage();

        self::assertSame(1, $usage->sessions->getTotal());
        self::assertSame(1, $usage->sections[0]->sessions->getTotal());
        self::assertSame(1, $this->row($usage, '610')->sessions->getTotal());
        self::assertSame(1, $this->row($usage, '620')->sessions->getTotal());
        self::assertSame(2, $usage->unitCount);
        self::assertSame(
            ['Payments:GroupList:default' => 1],
            $this->repository->sumByPage(new DateTimeImmutable('2026-03-01'), new DateTimeImmutable('2026-03-31')),
        );
    }

    public function testAKrajShowsItsOkresyAndItsOwnUnitsInOneRow(): void
    {
        $this->session('2026-03-02', '621.66', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');
        $this->session('2026-03-02', '621.01', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');
        $this->session('2026-03-02', '622', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');
        $this->session('2026-03-02', '620', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');
        $this->session('2026-03-02', '620.01', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');
        // Other kraje and codes that only look alike.
        $this->session('2026-03-02', '611.01', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');
        $this->session('2026-03-02', '6210.01', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');

        $usage = $this->usage(region: '620');

        self::assertSame(5, $usage->sessions->getTotal());
        self::assertSame(['620', '621', '622'], $this->codes($usage));
        self::assertSame('620 (kraj a jeho přímé jednotky)', $this->row($usage, '620')->label);
        self::assertSame(2, $this->row($usage, '621')->sessions->getTotal());
        self::assertSame('621', $this->row($usage, '621')->linkDistrict);
        self::assertSame('620', $this->row($usage, '621')->linkRegion);
        self::assertSame(5, $usage->unitCount);
    }

    public function testAnOkresShowsItsStrediskaAndItsOwnRolesInOneRow(): void
    {
        $this->session('2026-03-02', '621.66', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');
        $this->session('2026-03-02', '621.66', 'travel', 'Travel:Default', 'Travel:Default:default');
        $this->session('2026-03-02', '621.01', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');
        $this->session('2026-03-02', '621', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');
        // Another okres of the same kraj, and a number that only starts the same.
        $this->session('2026-03-02', '622.01', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');
        $this->session('2026-03-02', '6210.01', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');

        $usage = $this->usage(region: '620', district: '621');

        self::assertSame(4, $usage->sessions->getTotal());
        self::assertSame(['621', '621.01', '621.66'], $this->codes($usage));
        self::assertSame('621 (role okresu)', $this->row($usage, '621')->label);
        self::assertSame(1, $this->row($usage, '621.66')->get(UsageSection::TRAVEL)->getTotal());
        self::assertFalse($this->row($usage, '621.66')->hasUnits());
    }

    public function testOneMonthLimitsTheSessionsToThatMonth(): void
    {
        $this->session('2026-02-28', '622.01', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');
        $this->session('2026-03-01', '622.01', 'travel', 'Travel:Default', 'Travel:Default:default');
        $this->session('2026-03-31', '622.01', 'travel', 'Travel:Default', 'Travel:Default:default');
        $this->session('2026-04-01', '622.01', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');

        $usage = $this->usage(month: 3);

        self::assertSame([3], $usage->months);
        self::assertSame(2, $usage->sessions->getTotal());
        self::assertSame([UsageSection::TRAVEL], array_map(static fn ($section) => $section->section, $usage->sections));
    }

    public function testPageSessionsSumAllUnitsOfTheRange(): void
    {
        $this->session('2026-03-02', '622.01', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');
        $this->session('2026-03-03', '611.01', 'payments', 'Payments:GroupList', 'Payments:GroupList:default');
        $this->session('2026-03-03', '611.01', 'payments', 'Payments:Payment', 'Payments:Payment:default');
        $this->session('2026-04-03', '611.01', 'payments', 'Payments:Payment', 'Payments:Payment:default');

        self::assertSame(
            ['Payments:GroupList:default' => 2, 'Payments:Payment:default' => 1],
            $this->repository->sumByPage(new DateTimeImmutable('2026-03-01'), new DateTimeImmutable('2026-03-31')),
        );
    }

    /**
     * Records one session the way the tracker does: in the whole system down to the page and under the unit down
     * to the screen. A login that switched to another role is already counted in the whole system.
     */
    private function session(string $day, string $unitCode, string $section, string $screen, string $page, bool $newLogin = true): void
    {
        $date = new DateTimeImmutable($day);

        if ($newLogin) {
            $this->repository->increment($date, UsageUnitCode::ALL, '', '', '');
            $this->repository->increment($date, UsageUnitCode::ALL, $section, '', '');
            $this->repository->increment($date, UsageUnitCode::ALL, $section, $screen, '');
            $this->repository->increment($date, UsageUnitCode::ALL, $section, $screen, $page);
        }

        $this->repository->increment($date, $unitCode, '', '', '');
        $this->repository->increment($date, $unitCode, $section, '', '');
        $this->repository->increment($date, $unitCode, $section, $screen, '');
    }

    private function usage(?int $month = null, ?string $region = null, ?string $district = null): SystemUsage
    {
        return ($this->handler)(new SystemUsageQuery(2026, $month, $region, $district));
    }

    /** @return list<string> */
    private function codes(SystemUsage $usage): array
    {
        return array_map(static fn (UnitUsage $row): string => $row->code, $usage->rows);
    }

    private function row(SystemUsage $usage, string $code): UnitUsage
    {
        foreach ($usage->rows as $row) {
            if ($row->code === $code) {
                return $row;
            }
        }

        self::fail('Řádek '.$code.' chybí.');
    }
}
