<?php

declare(strict_types=1);

namespace Model\Common\ReadModel\QueryHandlers;

use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use Mockery;
use Model\Common\Member;
use Model\Common\ReadModel\Queries\MemberNamesQuery;
use Model\Common\Repositories\IMemberRepository;
use Model\Common\UnitId;

final class MemberNamesQueryHandlerTest extends Unit
{
    public function testOnlyMembersWithSameAgeOrOlderAreReturned(): void
    {
        $unitId = new UnitId(1);

        $members = Mockery::mock(IMemberRepository::class);
        $members->shouldReceive('findByUnit')
            ->once()
            ->withArgs([$unitId, true])
            ->andReturn([
                new Member(1, 'Adam', ChronosDate::now()->subYears(18)->addDays(1)),
                new Member(2, 'Petr', ChronosDate::now()->subYears(18)),
                new Member(3, 'Vojta', ChronosDate::now()->subYears(18)->subDays(1)),
                new Member(4, 'Julie', null),
            ]);

        $handler = new MemberNamesQueryHandler($members);

        self::assertSame(
            [
                2 => 'Petr',
                3 => 'Vojta',
            ],
            $handler(new MemberNamesQuery($unitId, 18)),
        );
    }
}
