<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Model\Common\Services\CommandBus;
use App\Model\Payment\PaymentService;
use Codeception\Test\Unit;
use Component\Forms\BaseForm;
use Kdyby\Replicator\Container as ReplicatorContainer;
use Mockery;
use Nette\Forms\Controls\TextInput;
use ReflectionMethod;

final class SplitPaymentDialogTest extends Unit
{
    /** @return array<string, array{string, bool}> */
    public function getAmounts(): array
    {
        return [
            'celé číslo' => ['300', true],
            'celé číslo v desetinném tvaru' => ['300,00', true],
            'dvě desetinná místa' => ['300,50', true],
            'tři desetinná místa' => ['300,555', false],
        ];
    }

    /** @dataProvider getAmounts */
    public function testSplitAmountAcceptsWholeNumbersAndAtMostTwoDecimalPlaces(string $amount, bool $isValid): void
    {
        $form = $this->buildForm();

        $splits = $form['splits'];
        self::assertInstanceOf(ReplicatorContainer::class, $splits);

        // Bez presenteru replikátor výchozí položku sám nevytvoří, tovární callback
        // s pravidly je ale stejný, jaký se použije při běhu aplikace.
        $split = $splits->createOne();
        $control = $split['amount'];
        self::assertInstanceOf(TextInput::class, $control);

        $control->setValue($amount);
        // Formulář sám o sobě není ukotvený v presenteru, tak se validuje jen pole.
        $control->validate();

        self::assertSame(
            $isValid ? [] : [PaymentFormFields::MONEY_PATTERN_MESSAGE],
            $control->getErrors(),
        );
    }

    private function buildForm(): BaseForm
    {
        // paymentId zůstává -1, takže se dialog na žádnou platbu neptá.
        $dialog = new SplitPaymentDialog(
            1,
            Mockery::mock(CommandBus::class),
            Mockery::mock(PaymentService::class),
        );

        $method = new ReflectionMethod($dialog, 'createComponentForm');
        $form = $method->invoke($dialog);
        self::assertInstanceOf(BaseForm::class, $form);

        return $form;
    }
}
