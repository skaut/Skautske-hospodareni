<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Model\Common\Services\QueryBus;
use App\Model\Common\UnitId;
use App\Model\DTO\Payment\Group;
use App\Model\DTO\Payment\GroupEmail;
use App\Model\Payment\EmailTemplate;
use App\Model\Payment\EmailType;
use App\Model\Payment\PaymentService;
use App\Model\Payment\ReadModel\Queries\BankAccount\BankAccountsAccessibleByUnitsQuery;
use App\Model\Payment\ReadModel\Queries\GroupEmailQuery;
use App\Model\Payment\ReadModel\Queries\NextVariableSymbolSequenceQuery;
use App\Model\Payment\ReadModel\Queries\OAuthsAccessibleByGroupsQuery;
use App\Model\Payment\VariableSymbol;
use App\Model\Unit\ReadModel\Queries\UnitsDetailQuery;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use Component\Forms\VariableSymbolControl;
use LogicException;
use Mockery;
use Nette\Forms\Container;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextInput;
use ReflectionMethod;

final class GroupFormTest extends Unit
{
    public function testCloneUsesSourceSettingsButRemainsUnsavedCreateForm(): void
    {
        $sourceGroupId = 42;
        $sourceGroup = new Group(
            $sourceGroupId,
            null,
            [123],
            null,
            'Zdrojová skupina',
            1500.5,
            new ChronosDate('2026-06-19'),
            308,
            new VariableSymbol('999'),
            'open',
            null,
            '',
            null,
            true,
            true,
            21,
        );

        $paymentService = Mockery::mock(PaymentService::class);
        $paymentService->shouldReceive('getGroup')
            ->with($sourceGroupId)
            ->andReturn($sourceGroup);
        $paymentService->shouldNotReceive('createGroup');
        $paymentService->shouldNotReceive('updateGroup');

        $queryBus = Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')
            ->andReturnUsing(static function (object $query): mixed {
                return match (true) {
                    $query instanceof NextVariableSymbolSequenceQuery => new VariableSymbol('2601'),
                    $query instanceof BankAccountsAccessibleByUnitsQuery => [],
                    $query instanceof OAuthsAccessibleByGroupsQuery => [],
                    $query instanceof UnitsDetailQuery => [],
                    $query instanceof GroupEmailQuery => new GroupEmail(
                        new EmailTemplate(
                            'Kopírovaný předmět '.$query->getEmailType()->toString(),
                            'Kopírované tělo',
                        ),
                        true,
                    ),
                    default => throw new LogicException('Neočekávaný query '.get_debug_type($query)),
                };
            });

        $component = new GroupForm(
            new UnitId(123),
            null,
            null,
            $sourceGroupId,
            $paymentService,
            $queryBus,
        );
        $method = new ReflectionMethod($component, 'createComponentForm');
        /** @var \Component\Forms\BaseForm $form */
        $form = $method->invoke($component);

        $name = $form['name'];
        $amount = $form['amount'];
        $constantSymbol = $form['constantSymbol'];
        $nextVs = $form['nextVs'];
        $automaticPairingEnabled = $form['automaticPairingEnabled'];
        $pairingDaysBack = $form['pairingDaysBack'];
        $emails = $form['emails'];

        self::assertInstanceOf(TextInput::class, $name);
        self::assertInstanceOf(TextInput::class, $amount);
        self::assertInstanceOf(TextInput::class, $constantSymbol);
        self::assertInstanceOf(VariableSymbolControl::class, $nextVs);
        self::assertInstanceOf(Checkbox::class, $automaticPairingEnabled);
        self::assertInstanceOf(TextInput::class, $pairingDaysBack);
        self::assertInstanceOf(Container::class, $emails);

        $paymentInfo = $emails[EmailType::PAYMENT_INFO];
        self::assertInstanceOf(Container::class, $paymentInfo);
        $paymentInfoSubject = $paymentInfo['subject'];
        self::assertInstanceOf(TextInput::class, $paymentInfoSubject);

        self::assertSame('Zdrojová skupina', $name->getValue());
        self::assertSame(1500.5, $amount->getValue());
        self::assertSame('308', (string) $constantSymbol->getValue());
        self::assertSame('2601', (string) $nextVs->getControl()->value);
        self::assertTrue($automaticPairingEnabled->getValue());
        self::assertSame('21', (string) $pairingDaysBack->getValue());
        self::assertSame(
            'Kopírovaný předmět payment_info',
            $paymentInfoSubject->getValue(),
        );

        $submit = $form['send'];
        self::assertInstanceOf(SubmitButton::class, $submit);
        self::assertSame('Založit skupinu', $submit->getCaption());

        $name->setValue('');
        $nextVs->setValue('2601');
        $form->validate();
        self::assertSame(['Musíte zadat název skupiny'], $name->getErrors());
    }
}
