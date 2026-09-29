<?php

declare(strict_types=1);

namespace App\Components\Payment;

use Codeception\Test\Unit;
use Component\Forms\BaseForm;
use Nette\Forms\Controls\TextInput;

final class PaymentFormFieldsTest extends Unit
{
    /** @return array<string, array{string, bool}> */
    public function getAmounts(): array
    {
        return [
            // Desetinná část není povinná – celé číslo musí projít tak, jak ho uživatel napíše.
            'celé číslo' => ['123', true],
            'celé číslo s mezerou jako oddělovačem tisíců' => ['1 234', true],
            'celé číslo v desetinném tvaru s čárkou' => ['123,00', true],
            'celé číslo v desetinném tvaru s tečkou' => ['123.00', true],
            'celé číslo s jedním desetinným místem' => ['123,0', true],
            'jedno desetinné místo' => ['100.9', true],
            'dvě desetinná místa s tečkou' => ['100.99', true],
            'dvě desetinná místa s čárkou' => ['100,99', true],
            'haléře v tisících' => ['1 000,50', true],
            'tři desetinná místa s tečkou' => ['100.999', false],
            'tři desetinná místa s čárkou' => ['100,999', false],
            'zlomek haléře' => ['0.005', false],
        ];
    }

    /** @dataProvider getAmounts */
    public function testAmountAcceptsAtMostTwoDecimalPlaces(string $amount, bool $isValid): void
    {
        $form = new BaseForm();
        PaymentFormFields::addAmount($form);

        $control = $form['amount'];
        self::assertInstanceOf(TextInput::class, $control);
        $control->setValue($amount);

        $form->validate();

        self::assertSame(
            $isValid ? [] : [PaymentFormFields::MONEY_PATTERN_MESSAGE],
            $control->getErrors(),
        );
    }
}
