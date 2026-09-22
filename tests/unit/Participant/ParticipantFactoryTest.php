<?php

declare(strict_types=1);

namespace App\Model\Participant;

use App\Model\DTO\Payment\ParticipantFactory as ParticipantDtoFactory;
use App\Model\Participant\Payment\Event;
use App\Model\Participant\Payment\EventType;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;

final class ParticipantFactoryTest extends Unit
{
    public function testParticipantWithoutUnitIsNonMember(): void
    {
        $participant = $this->createParticipant(null);

        $this->assertTrue(ParticipantDtoFactory::create($participant)->isNonMember());
    }

    public function testParticipantWithUnitIsMember(): void
    {
        $participant = $this->createParticipant(123);

        $this->assertFalse(ParticipantDtoFactory::create($participant)->isNonMember());
    }

    private function createParticipant(?int $unitId): Participant
    {
        return new Participant(
            1,
            2,
            'Jana',
            'Nováková',
            null,
            14,
            new ChronosDate('2012-05-10'),
            'Skautská 1',
            'Praha',
            10000,
            'Česká republika',
            $unitId,
            '',
            '',
            7,
            true,
            PaymentFactory::createDefault(1, new Event(1, EventType::CAMP())),
            null,
        );
    }
}
