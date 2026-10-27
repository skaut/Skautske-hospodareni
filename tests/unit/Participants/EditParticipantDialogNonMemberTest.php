<?php

declare(strict_types=1);

namespace App\Components\Participants;

use App\Model\Common\Repositories\IParticipantRepository;
use App\Model\DTO\Participant\NonMemberParticipant;
use App\Model\DTO\Participant\Participant;
use App\Model\DTO\Participant\UpdateParticipant;
use App\Model\Participant\NonMemberParticipantService;
use App\Model\Participant\Payment;
use App\Model\Participant\Payment\Event;
use App\Model\Participant\Payment\EventType;
use App\Model\Participant\PaymentId;
use App\Model\Participant\PersonAddressChangeNotAllowed;
use App\Model\Utils\MoneyFactory;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use Component\Forms\BaseForm;
use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use ReflectionMethod;
use RuntimeException;

final class EditParticipantDialogNonMemberTest extends Unit
{
    private const PARTICIPANT_ID = 363533;
    private const PERSON_ID = 472796;

    public function testChangingOnlyPaymentDoesNotSendPersonDataToSkautis(): void
    {
        $repository = $this->createRepository();
        $repository->shouldNotReceive('updateNonMemberParticipant');

        [$dialog, $form] = $this->buildDialog($repository);
        $updates = [];
        $dialog->onUpdate[] = static function (int $participantId, array $changes) use (&$updates): void {
            $updates[] = $changes;
        };

        $this->submit($form, ['payment' => '150']);

        self::assertSame([[UpdateParticipant::FIELD_PAYMENT => '150.00']], $updates);
        self::assertFalse($form->hasErrors());
    }

    public function testRejectedAddressChangeShowsFormErrorAndKeepsDialogOpen(): void
    {
        $repository = $this->createRepository();
        $repository->shouldReceive('updateNonMemberParticipant')
            ->once()
            ->with(self::PERSON_ID, Mockery::on(static fn (NonMemberParticipant $p): bool => $p->getStreet() === 'Nová 1'))
            ->andThrow(new PersonAddressChangeNotAllowed(self::PERSON_ID, new RuntimeException()));

        [$dialog, $form] = $this->buildDialog($repository);
        $dialog->onUpdate[] = static function (): void {
            self::fail('Participant must not be updated when SkautIS rejects the address change.');
        };

        $this->submit($form, ['street' => 'Nová 1', 'payment' => '150']);

        self::assertSame([EditParticipantDialog::ADDRESS_CHANGE_NOT_ALLOWED_MESSAGE], $form->getOwnErrors());
        self::assertTrue($dialog->opened);
    }

    private function createRepository(): IParticipantRepository&MockInterface
    {
        $repository = Mockery::mock(IParticipantRepository::class);
        $repository->shouldReceive('getNonMemberParticipant')
            ->with(self::PERSON_ID)
            ->andReturn(new NonMemberParticipant(
                'Justýna',
                'Navrátilová',
                null,
                'female',
                new ChronosDate('2019-10-14'),
                'Bukovinka 105',
                'Bukovinka',
                67905,
            ));

        return $repository;
    }

    /** @return array{EditParticipantDialog, BaseForm} */
    private function buildDialog(IParticipantRepository $repository): array
    {
        $participant = new Participant(
            self::PARTICIPANT_ID,
            self::PERSON_ID,
            'Justýna',
            'Navrátilová',
            null,
            7,
            new ChronosDate('2019-10-14'),
            'Bukovinka 105',
            'Bukovinka',
            67905,
            'Jihomoravský',
            '',
            '',
            3,
            true,
            new Payment(
                PaymentId::fromString('11bf5b37-e0b8-42e0-8dcf-dc8c4aefc000'),
                self::PARTICIPANT_ID,
                new Event(28388, EventType::GENERAL()),
                MoneyFactory::fromDecimal('100'),
                MoneyFactory::zero(),
            ),
            null,
            true,
        );

        $dialog = new EditParticipantDialog(
            [self::PARTICIPANT_ID => $participant],
            false,
            false,
            false,
            false,
            true,
            new NonMemberParticipantService($repository),
        );
        $dialog->participantId = self::PARTICIPANT_ID;
        $dialog->opened = true;

        $form = (new ReflectionMethod($dialog, 'createComponentForm'))->invoke($dialog);
        self::assertInstanceOf(BaseForm::class, $form);

        return [$dialog, $form];
    }

    /** @param array<string, string> $changedValues */
    private function submit(BaseForm $form, array $changedValues): void
    {
        $values = $changedValues + [
            'firstName' => 'Justýna',
            'lastName' => 'Navrátilová',
            'street' => 'Bukovinka 105',
            'city' => 'Bukovinka',
            'postcode' => '67905',
            'nick' => '',
            'sex' => 'female',
            'birthday' => new DateTimeImmutable('2019-10-14'),
            'payment' => '100',
        ];

        foreach ($form->onSuccess as $handler) {
            $handler($form, $values);
        }
    }
}
