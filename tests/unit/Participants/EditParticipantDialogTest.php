<?php

declare(strict_types=1);

namespace App\Components\Participants;

use App\Components\Payment\PaymentFormFields;
use App\Model\DTO\Participant\Participant;
use App\Model\Participant\Payment;
use App\Model\Participant\Payment\Event;
use App\Model\Participant\Payment\EventType;
use App\Model\Participant\PaymentId;
use App\Model\Utils\MoneyFactory;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use Component\Forms\BaseForm;
use Nette\Forms\Controls\TextInput;
use ReflectionMethod;
use ReflectionProperty;

final class EditParticipantDialogTest extends Unit
{
    private const PARTICIPANT_ID = 42;

    public function testFormPrefillsAmountsIncludingCents(): void
    {
        [$payment, $repayment] = $this->buildForm();

        self::assertSame('1500.55', $payment->getValue());
        self::assertSame('100.25', $repayment->getValue());
    }

    /** @return array<string, array{string, bool}> */
    public function getAmounts(): array
    {
        return [
            'celé číslo' => ['123', true],
            'celé číslo v desetinném tvaru s čárkou' => ['123,00', true],
            'celé číslo v desetinném tvaru s tečkou' => ['123.00', true],
            'dvě desetinná místa' => ['123,45', true],
            'tři desetinná místa' => ['123,455', false],
        ];
    }

    /** @dataProvider getAmounts */
    public function testAmountsAcceptWholeNumbersAndAtMostTwoDecimalPlaces(string $amount, bool $isValid): void
    {
        [$payment, $repayment] = $this->buildForm();

        $payment->setValue($amount);
        $repayment->setValue($amount);
        $payment->validate();
        $repayment->validate();

        $expected = $isValid ? [] : [PaymentFormFields::MONEY_PATTERN_MESSAGE];
        self::assertSame($expected, $payment->getErrors());
        self::assertSame($expected, $repayment->getErrors());
    }

    /** @return array{TextInput, TextInput, BaseForm} */
    private function buildForm(): array
    {
        $participant = new Participant(
            self::PARTICIPANT_ID,
            987,
            'Jan',
            'Novák',
            null,
            30,
            new ChronosDate('1996-05-01'),
            'Ulice 1',
            'Brno',
            60200,
            'Jihomoravský',
            '93. oddíl',
            '123.45',
            7,
            true,
            new Payment(
                PaymentId::fromString('11bf5b37-e0b8-42e0-8dcf-dc8c4aefc000'),
                self::PARTICIPANT_ID,
                new Event(1, EventType::GENERAL()),
                MoneyFactory::fromDecimal('1500.55'),
                MoneyFactory::fromDecimal('100.25'),
            ),
            null,
        );

        $dialog = new EditParticipantDialog([self::PARTICIPANT_ID => $participant], true, true, true, false);

        $participantId = new ReflectionProperty($dialog, 'participantId');
        $participantId->setValue($dialog, self::PARTICIPANT_ID);

        $method = new ReflectionMethod($dialog, 'createComponentForm');
        $form = $method->invoke($dialog);
        self::assertInstanceOf(BaseForm::class, $form);

        $payment = $form['payment'];
        $repayment = $form['repayment'];
        self::assertInstanceOf(TextInput::class, $payment);
        self::assertInstanceOf(TextInput::class, $repayment);

        return [$payment, $repayment, $form];
    }
}
