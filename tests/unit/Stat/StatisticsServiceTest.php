<?php

declare(strict_types=1);

namespace App\Model\Stat;

use App\Model\Common\Services\QueryBus;
use App\Model\Common\UnitId;
use App\Model\DTO\Stat\Counter;
use App\Model\Event\ReadModel\Queries\CampListQuery;
use App\Model\Event\ReadModel\Queries\CampStatisticsQuery;
use App\Model\Event\ReadModel\Queries\EventListQuery;
use App\Model\Event\ReadModel\Queries\EventStatisticsQuery;
use App\Model\Skautis\ISkautisEvent;
use App\Model\Stat\ReadModel\Queries\LocalUnitStatisticsQuery;
use App\Model\Unit\Unit;
use Codeception\Test\Unit as TestCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

final class StatisticsServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testStatisticsAreAggregatedByUnitTreeWithoutCountingCashbookItemsAsEvents(): void
    {
        $root = $this->createUnit(10, [
            $this->createUnit(20, [
                $this->createUnit(30),
            ]),
        ]);
        $queryBus = Mockery::mock(QueryBus::class);

        $queryBus->shouldReceive('handle')
            ->with(Mockery::type(EventListQuery::class))
            ->once()
            ->andReturn([
                10 => $this->createEvent(20, 'draft'),
                11 => $this->createEvent(30, 'closed'),
                12 => $this->createEvent(30, 'cancelled'),
            ]);
        $queryBus->shouldReceive('handle')
            ->with(Mockery::type(CampListQuery::class))
            ->once()
            ->andReturn([
                20 => $this->createEvent(30, 'real', true),
            ]);
        $queryBus->shouldReceive('handle')
            ->with(Mockery::type(EventStatisticsQuery::class))
            ->once()
            ->andReturn([
                10 => 5,
                11 => 2,
            ]);
        $queryBus->shouldReceive('handle')
            ->with(Mockery::type(CampStatisticsQuery::class))
            ->once()
            ->andReturn([
                20 => 4,
            ]);
        $queryBus->shouldReceive('handle')
            ->with(Mockery::on(static function (LocalUnitStatisticsQuery $query): bool {
                return $query->getUnitIds() === [10, 20, 30] && $query->getYear() === 2026;
            }))
            ->once()
            ->andReturn($this->createLocalStatistics());

        $statistics = (new StatisticsService($queryBus))->getEventStatistics($root, 2026);

        self::assertSame(2, $statistics[10]->getEvents());
        self::assertSame(1, $statistics[10]->getCamps());
        self::assertSame(3, $statistics[10]->getPaymentGroups());
        self::assertSame(2, $statistics[20]->getEvents());
        self::assertSame(1, $statistics[20]->getCamps());
        self::assertSame(3, $statistics[20]->getPaymentGroups());
        self::assertSame(1, $statistics[30]->getEvents());
        self::assertSame(1, $statistics[30]->getCamps());
        self::assertSame(3, $statistics[30]->getPaymentGroups());
        self::assertSame(2, $statistics[30]->getEventTotal());
        self::assertSame(1, $statistics[30]->getEventWithExpense());
        self::assertSame(1, $statistics[30]->getEventWithoutExpense());
        self::assertSame(1, $statistics[30]->getCampTotal());
        self::assertSame(1, $statistics[30]->getCampWithExpense());
        self::assertSame(1, $statistics[30]->getCampWithParticipantStats());
    }

    /** @param Unit[] $children */
    private function createUnit(int $id, array $children = []): Unit
    {
        return new Unit(
            $id,
            (string) $id,
            'Jednotka '.$id,
            null,
            '',
            '',
            '',
            (string) $id,
            'stredisko',
            null,
            $children,
        );
    }

    /** @return array<int, Counter> */
    private function createLocalStatistics(): array
    {
        $counter = new Counter();
        $counter->addPaymentStats(2, 1, 5, 2, 2, 1, 1500.0, 700.0, 2);

        return [30 => $counter];
    }

    private function createEvent(int $unitId, string $state, bool $withParticipantStatistics = false): ISkautisEvent
    {
        return new class($unitId, $state, $withParticipantStatistics) implements ISkautisEvent {
            public function __construct(private int $unitId, private string $state, private bool $withParticipantStatistics)
            {
            }

            public function getUnitId(): UnitId
            {
                return new UnitId($this->unitId);
            }

            public function getState(): string
            {
                return $this->state;
            }

            public function getParticipantStatistics(): ?object
            {
                return $this->withParticipantStatistics ? new class {
                } : null;
            }
        };
    }
}
