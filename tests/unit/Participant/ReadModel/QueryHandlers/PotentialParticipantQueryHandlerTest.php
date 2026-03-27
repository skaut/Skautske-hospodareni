<?php

declare(strict_types=1);

namespace App\Model\Participant\ReadModel\QueryHandlers;

use App\Model\Common\Member;
use App\Model\Common\Repositories\IMemberRepository;
use App\Model\Common\UnitId;
use App\Model\DTO\Participant\Participant;
use App\Model\Participant\ReadModel\Queries\PotentialParticipantListQuery;
use Codeception\Test\Unit;
use Mockery;

final class PotentialParticipantQueryHandlerTest extends Unit
{
    /** @dataProvider dataDirectMembersOnly */
    public function testReturnAllMembersThatAreNotAlreadyParticipants(bool $directMembersOnly): void
    {
        $unitId = new UnitId(1);

        $members = Mockery::mock(IMemberRepository::class);
        $members
            ->shouldReceive('findByUnit')
            ->once()
            ->withArgs([$unitId, ! $directMembersOnly])
            ->andReturn([
                new Member(3, 'á', null),
                new Member(2, 'b', null),
                new Member(1, 'First', null),
            ]);

        $handler = new PotentialParticipantListQueryHandler($members);

        $participants = [Mockery::mock(Participant::class, ['getPersonId' => 1])];

        self::assertSame(
            [
                3 => 'á',
                2 => 'b',
            ],
            $handler(new PotentialParticipantListQuery($unitId, $directMembersOnly, $participants)),
        );
    }

    /** @return bool[][] */
    public function dataDirectMembersOnly(): array
    {
        return [[true], [false]];
    }
}
