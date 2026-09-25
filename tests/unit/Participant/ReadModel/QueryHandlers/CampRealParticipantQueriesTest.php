<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\CampParticipantStatisticsQuery;
use App\Model\Cashbook\ReadModel\Queries\CampPragueParticipantsQuery;
use App\Model\Cashbook\ReadModel\Queries\CampRealParticipantListQuery;
use App\Model\Common\Repositories\IParticipantRepository;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Participant\Participant;
use App\Model\Event\SkautisCampId;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use Mockery;

final class CampRealParticipantQueriesTest extends Unit
{
    public function testRealParticipantListUsesDedicatedRepositoryMethod(): void
    {
        $campId = new SkautisCampId(42);
        $participants = [Mockery::mock(Participant::class)];
        $repository = Mockery::mock(IParticipantRepository::class);
        $repository->shouldReceive('findRealByCamp')->once()->with($campId)->andReturn($participants);

        self::assertSame($participants, (new CampRealParticipantListQueryHandler($repository))(new CampRealParticipantListQuery($campId)));
    }

    public function testCampStatisticsUseOnlyRealParticipantList(): void
    {
        $campId = new SkautisCampId(42);
        $queryBus = Mockery::mock(QueryBus::class);
        $queryBus
            ->shouldReceive('handle')
            ->once()
            ->withArgs(static fn (CampRealParticipantListQuery $query): bool => $query->getCampId()->toInt() === 42)
            ->andReturn([
                Mockery::mock(Participant::class, ['getDays' => 4]),
                Mockery::mock(Participant::class, ['getDays' => 2]),
            ]);

        $statistics = (new CampParticipantStatisticsQueryHandler($queryBus))(new CampParticipantStatisticsQuery($campId));

        self::assertSame(2, $statistics->getPersonsCount());
        self::assertSame(6, $statistics->getPersonDays());
    }

    public function testPragueStatisticsUseOnlyRealParticipantList(): void
    {
        $campId = new SkautisCampId(42);
        $queryBus = Mockery::mock(QueryBus::class);
        $queryBus
            ->shouldReceive('handle')
            ->once()
            ->withArgs(static fn (CampRealParticipantListQuery $query): bool => $query->getCampId()->toInt() === 42)
            ->andReturn([
                Mockery::mock(Participant::class, [
                    'getCity' => 'Praha',
                    'getBirthday' => new ChronosDate('2010-01-01'),
                    'getDays' => 5,
                ]),
            ]);

        $statistics = (new CampPragueParticipantsQueryHandler($queryBus))(
            new CampPragueParticipantsQuery($campId, '110.01', new ChronosDate('2026-07-01')),
        );

        self::assertNotNull($statistics);
        self::assertSame(1, $statistics->getCitizensCount());
        self::assertSame(5, $statistics->getPersonDaysUnder26());
    }
}
