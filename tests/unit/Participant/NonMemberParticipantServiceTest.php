<?php

declare(strict_types=1);

namespace App\Model\Participant;

use App\Model\Common\Repositories\IParticipantRepository;
use App\Model\DTO\Participant\NonMemberParticipant;
use App\Model\DTO\Participant\Participant;
use Codeception\Test\Unit;
use Mockery;
use RuntimeException;

final class NonMemberParticipantServiceTest extends Unit
{
    /** Účastník, k jehož osobním údajům uživatel nemá ve skautISu přístup, nesmí shodit načtení ostatních. */
    public function testFindsPersonalDataOfNonMembersOnly(): void
    {
        $incomplete = $this->nonMember('');
        $complete = $this->nonMember('female');

        $repository = Mockery::mock(IParticipantRepository::class);
        $repository->shouldReceive('getNonMemberParticipant')->once()->with(102)->andReturn($incomplete);
        $repository->shouldReceive('getNonMemberParticipant')
            ->once()
            ->with(103)
            ->andThrow(new PersonAccessNotAllowed(103, new RuntimeException()));
        $repository->shouldReceive('getNonMemberParticipant')->once()->with(104)->andReturn($complete);

        $service = new NonMemberParticipantService($repository);

        $this->assertSame([
            12 => $incomplete,
            13 => null,
            14 => $complete,
        ], $service->findNonMembers([
            $this->participant(10, false, 101),
            $this->participant(12, true, 102),
            $this->participant(13, true, 103),
            $this->participant(14, true, 104),
        ]));
    }

    private function participant(int $id, bool $isNonMember, int $personId): Participant
    {
        $participant = Mockery::mock(Participant::class);
        $participant->shouldReceive('isNonMember')->andReturn($isNonMember);
        $participant->shouldReceive('getId')->andReturn($id);
        $participant->shouldReceive('getPersonId')->andReturn($personId);

        return $participant;
    }

    private function nonMember(string $sex): NonMemberParticipant
    {
        return new NonMemberParticipant('Jana', 'Nováková', null, $sex, null, 'Skautská 1', 'Praha', '10000');
    }

    protected function _after(): void
    {
        Mockery::close();
    }
}
