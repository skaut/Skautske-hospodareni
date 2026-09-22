<?php

declare(strict_types=1);

namespace App\Model\Participant;

use App\Model\Common\Repositories\IParticipantRepository;
use App\Model\DTO\Participant\NonMemberParticipant;
use App\Model\DTO\Participant\Participant;
use Codeception\Test\Unit;
use Mockery;

final class NonMemberParticipantServiceTest extends Unit
{
    public function testFindsOnlyNonMembersWithoutSex(): void
    {
        $repository = Mockery::mock(IParticipantRepository::class);
        $repository->shouldReceive('getNonMemberParticipant')
            ->once()
            ->with(102)
            ->andReturn($this->nonMember(''));
        $repository->shouldReceive('getNonMemberParticipant')
            ->once()
            ->with(103)
            ->andReturn($this->nonMember('female'));

        $service = new NonMemberParticipantService($repository);

        $this->assertSame([
            12 => true,
        ], $service->findParticipantsWithMissingSex([
            $this->participant(10, false, null),
            $this->participant(12, true, 102, true),
            $this->participant(13, true, 103),
        ]));
    }

    private function participant(int $id, bool $isNonMember, ?int $personId, bool $expectsId = false): Participant
    {
        $participant = Mockery::mock(Participant::class);
        $participant->shouldReceive('isNonMember')->once()->andReturn($isNonMember);

        if ($isNonMember) {
            $participant->shouldReceive('getPersonId')->once()->andReturn($personId);
            if ($expectsId) {
                $participant->shouldReceive('getId')->once()->andReturn($id);
            }
        }

        return $participant;
    }

    private function nonMember(string $sex): NonMemberParticipant
    {
        return new NonMemberParticipant('Jana', 'Nováková', null, $sex, null, 'Skautská 1', 'Praha', 10000);
    }
}
