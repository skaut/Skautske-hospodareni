<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Model\Common\Services\CommandBus;
use App\Model\DTO\Payment\Group;
use App\Model\Payment\PaymentService;
use App\Model\Utils\MoneyFactory;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use Component\Forms\BaseContainer;
use Component\Forms\BaseForm;
use Mockery;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\TextInput;

final class MassAddFormTest extends Unit
{
    private const GROUP_ID = 7;

    /** @return array<string, array{string, bool}> */
    public function getAmounts(): array
    {
        return [
            'celé číslo' => ['500', true],
            'celé číslo v desetinném tvaru s čárkou' => ['500,00', true],
            'celé číslo v desetinném tvaru s tečkou' => ['500.00', true],
            'dvě desetinná místa' => ['500,25', true],
            'tři desetinná místa' => ['500,255', false],
        ];
    }

    /** @dataProvider getAmounts */
    public function testDefaultAmountAcceptsWholeNumbersAndAtMostTwoDecimalPlaces(string $amount, bool $isValid): void
    {
        $form = $this->buildComponent()['form'];
        self::assertInstanceOf(BaseForm::class, $form);

        $control = $form['amount'];
        self::assertInstanceOf(TextInput::class, $control);

        $control->setValue($amount);
        $control->validate();

        self::assertSame(
            $isValid ? [] : [PaymentFormFields::MONEY_PATTERN_MESSAGE],
            $control->getErrors(),
        );
    }

    /** @dataProvider getAmounts */
    public function testPersonAmountAcceptsWholeNumbersAndAtMostTwoDecimalPlaces(string $amount, bool $isValid): void
    {
        $component = $this->buildComponent();
        $component->addPerson(11, [], 'Jan Novák', MoneyFactory::fromDecimal('100.00'));

        $form = $component['form'];
        self::assertInstanceOf(BaseForm::class, $form);
        $persons = $form['persons'];
        self::assertInstanceOf(BaseContainer::class, $persons);
        $person = $persons['person11'];
        self::assertInstanceOf(BaseContainer::class, $person);

        // Pravidla u osoby platí jen když je zaškrtnutá.
        $selected = $person['selected'];
        self::assertInstanceOf(Checkbox::class, $selected);
        $selected->setValue(true);

        $control = $person['amount'];
        self::assertInstanceOf(TextInput::class, $control);
        // Předvyplněná částka musí zůstat platná i pro celé koruny.
        self::assertSame('100.00', $control->getValue());

        $control->setValue($amount);
        $control->validate();

        self::assertSame(
            $isValid ? [] : [PaymentFormFields::MONEY_PATTERN_MESSAGE],
            $control->getErrors(),
        );
    }

    private function buildComponent(): MassAddForm
    {
        $group = new Group(
            self::GROUP_ID,
            null,
            [123],
            null,
            'Skupina',
            MoneyFactory::fromDecimal('100.00'),
            new ChronosDate('2026-06-19'),
            308,
            null,
            'open',
            null,
            '',
            null,
        );

        $payments = Mockery::mock(PaymentService::class);
        $payments->shouldReceive('getGroup')
            ->with(self::GROUP_ID)
            ->andReturn($group);

        return new MassAddForm(self::GROUP_ID, $payments, Mockery::mock(CommandBus::class));
    }
}
