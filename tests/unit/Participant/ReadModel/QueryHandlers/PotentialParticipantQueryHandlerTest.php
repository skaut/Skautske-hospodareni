<?php

declare(strict_types=1);

namespace Model\Participant\ReadModel\QueryHandlers;

use Codeception\Test\Unit;
use Mockery;
use Model\Common\Member;
use Model\Common\Repositories\IMemberRepository;
use Model\Common\UnitId;
use Model\DTO\Participant\Participant;
use Model\Participant\ReadModel\Queries\PotentialParticipantListQuery;

final class PotentialParticipantQueryHandlerTest extends Unit
{
    /**
     * @dataProvider dataDirectMembersOnly
     */
    public function testReturnAllMembersThatAreNotAlreadyParticipants(bool $directMembersOnly) : void
    {
        $unitId = new UnitId(1);

        $members = Mockery::mock(IMemberRepository::class);
        $members
            ->shouldReceive('findByUnit')
            ->once()
            ->withArgs([$unitId, ! $directMembersOnly])
            ->andReturn([
                new Member(1, 'First'),
                new Member(2, 'b'),
                new Member(3, 'á'),
            ]);

        $handler = new PotentialParticipantListQueryHandler($members);

        $participants = [Mockery::mock(Participant::class, ['getPersonId' => 1])];

        self::assertSame(
            [
                3 => 'á', // Results should be sorted
                2 => 'b',
            ],
            $handler(new PotentialParticipantListQuery($unitId, $directMembersOnly, $participants))
        );
    }

    /**
     * @return bool[][]
     */
    public function dataDirectMembersOnly() : array
    {
        return [[true], [false]];
    }
}
