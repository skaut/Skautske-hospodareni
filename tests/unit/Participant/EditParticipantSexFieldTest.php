<?php

declare(strict_types=1);

namespace App\Components\Participants;

use App\Model\Common\Repositories\IParticipantRepository;
use App\Model\DTO\Participant\NonMemberParticipant;
use App\Model\DTO\Participant\Participant;
use App\Model\Participant\NonMemberParticipantService;
use App\Model\Utils\MoneyFactory;
use Codeception\Test\Unit;
use Mockery;
use ReflectionMethod;

final class EditParticipantSexFieldTest extends Unit
{
    public function testEditFormHandlesMissingSexAndPreservesSelectedSex(): void
    {
        foreach (['' => null, 'male' => 'male', 'female' => 'female'] as $sex => $expected) {
            $participant = Mockery::mock(Participant::class);
            $participant->shouldReceive('isNonMember')->once()->andReturn(true);
            $participant->shouldReceive('getPersonId')->once()->andReturn(123);
            $participant->shouldReceive('getPayment')->once()->andReturn(MoneyFactory::zero());

            $repository = Mockery::mock(IParticipantRepository::class);
            $repository->shouldReceive('getNonMemberParticipant')
                ->once()
                ->with(123)
                ->andReturn(new NonMemberParticipant('Jana', 'Nováková', null, $sex, null, '', '', 0));

            $dialog = new EditParticipantDialog(
                [1 => $participant],
                false,
                false,
                false,
                false,
                true,
                new NonMemberParticipantService($repository),
            );
            $dialog->participantId = 1;

            $form = (new ReflectionMethod($dialog, 'createComponentForm'))->invoke($dialog);

            $this->assertSame($expected, $form['sex']->getValue());
            $this->assertTrue($form['sex']->isRequired());
        }
    }
}
