<?php

declare(strict_types=1);

namespace App\Presentation\Payments\Repayment;

use App\Components\Payment\PaymentFormFields;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Payment\Group;
use App\Model\DTO\Payment\RepaymentCandidate;
use App\Model\Payment\BankAccountService;
use App\Model\Payment\PaymentService;
use App\Model\Utils\MoneyFactory;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use Component\Forms\BaseForm;
use Mockery;
use Nette\Forms\Container;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\TextInput;
use ReflectionMethod;
use ReflectionProperty;

final class RepaymentFormTest extends Unit
{
    private const PAYMENT_ID = 55;

    /** @return array<string, array{string, bool}> */
    public function getAmounts(): array
    {
        return [
            'celé číslo' => ['400', true],
            'celé číslo v desetinném tvaru s čárkou' => ['400,00', true],
            'celé číslo v desetinném tvaru s tečkou' => ['400.00', true],
            'dvě desetinná místa' => ['400,25', true],
            'tři desetinná místa' => ['400,255', false],
        ];
    }

    public function testAmountIsPrefilledFromMoneyIncludingCents(): void
    {
        $control = $this->buildAmountControl();

        self::assertSame('1500.55', $control->getValue());
    }

    /** @dataProvider getAmounts */
    public function testAmountAcceptsWholeNumbersAndAtMostTwoDecimalPlaces(string $amount, bool $isValid): void
    {
        $control = $this->buildAmountControl();

        $control->setValue($amount);
        $control->validate();

        self::assertSame(
            $isValid ? [] : [PaymentFormFields::MONEY_PATTERN_MESSAGE],
            $control->getErrors(),
        );
    }

    private function buildAmountControl(): TextInput
    {
        $candidate = new RepaymentCandidate(
            self::PAYMENT_ID,
            987,
            'Jan Novák',
            MoneyFactory::fromDecimal('1500.55'),
            null,
        );

        $group = new Group(
            7,
            null,
            [123],
            null,
            'Skupina',
            null,
            new ChronosDate('2026-06-19'),
            null,
            null,
            'open',
            null,
            '',
            null,
        );

        $queryBus = Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')->andReturn([$candidate]);

        $presenter = new RepaymentPresenter(
            Mockery::mock(PaymentService::class),
            Mockery::mock(BankAccountService::class),
        );

        (new ReflectionProperty($presenter, 'queryBus'))->setValue($presenter, $queryBus);
        (new ReflectionProperty($presenter, 'group'))->setValue($presenter, $group);

        $form = (new ReflectionMethod($presenter, 'createComponentForm'))->invoke($presenter);
        self::assertInstanceOf(BaseForm::class, $form);

        $payments = $form['payments'];
        self::assertInstanceOf(Container::class, $payments);
        $container = $payments['payment'.self::PAYMENT_ID];
        self::assertInstanceOf(Container::class, $container);

        // Pravidla se uplatní jen u vybraných vratek.
        $selected = $container['selected'];
        self::assertInstanceOf(Checkbox::class, $selected);
        $selected->setValue(true);

        $control = $container['amount'];
        self::assertInstanceOf(TextInput::class, $control);

        return $control;
    }
}
