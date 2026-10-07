<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Model\Common\EmailAddress;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Payment\MemberEmail;
use App\Model\DTO\Payment\MemberEmailType;
use App\Model\DTO\Payment\Payment;
use App\Model\Payment\Payment\State;
use App\Model\Payment\PaymentService;
use App\Model\Payment\ReadModel\Queries\MemberEmailsQuery;
use App\Model\Payment\VariableSymbol;
use App\Model\Utils\MoneyFactory;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use Component\Forms\BaseForm;
use Component\Forms\EmailListControl;
use Mockery;
use ReflectionMethod;

use function array_map;

final class PaymentDialogTest extends Unit
{
    private const GROUP_ID = 5;
    private const PAYMENT_ID = 7;
    private const PERSON_ID = 147006;

    public function testEditOffersMemberContactsFromSkautisWithCurrentRecipientsChecked(): void
    {
        $queryBus = Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')
            ->once()
            ->withArgs(static fn (object $query): bool => $query instanceof MemberEmailsQuery && $query->getMemberId() === self::PERSON_ID)
            ->andReturn([
                new MemberEmail('jan@example.com', 'E-mail (hlavní) – jan@example.com', MemberEmailType::MAIN),
                new MemberEmail('matka@example.com', 'Matka – matka@example.com', MemberEmailType::MOTHER),
            ]);

        $form = $this->editForm($this->payment(self::PERSON_ID, ['matka@example.com', 'jiny@example.com']), $queryBus);

        $emails = $form['emails'];
        self::assertInstanceOf(EmailListControl::class, $emails);
        self::assertSame(['jan@example.com' => 'Hlavní', 'matka@example.com' => 'Matka'], $emails->getOffered());
        self::assertSame(['matka@example.com', 'jiny@example.com'], $emails->getValue());
    }

    public function testEditOfPaymentWithoutPersonDoesNotAskSkautis(): void
    {
        $queryBus = Mockery::mock(QueryBus::class);
        $queryBus->shouldNotReceive('handle');

        $form = $this->editForm($this->payment(null, ['jan@example.com']), $queryBus);

        $emails = $form['emails'];
        self::assertInstanceOf(EmailListControl::class, $emails);
        self::assertSame([], $emails->getOffered());
        self::assertSame(['jan@example.com'], $emails->getValue());
    }

    /** @param list<string> $recipients */
    private function payment(?int $personId, array $recipients): Payment
    {
        return new Payment(
            self::PAYMENT_ID,
            'Testovací platba',
            MoneyFactory::fromDecimal('500.00'),
            array_map(static fn (string $address): EmailAddress => new EmailAddress($address), $recipients),
            new ChronosDate('2026-10-20'),
            new VariableSymbol('123'),
            null,
            '',
            false,
            State::get(State::PREPARING),
            null,
            null,
            null,
            $personId,
            self::GROUP_ID,
            [],
        );
    }

    private function editForm(Payment $payment, QueryBus $queryBus): BaseForm
    {
        $paymentService = Mockery::mock(PaymentService::class);
        $paymentService->shouldReceive('findPayment')
            ->with(self::PAYMENT_ID)
            ->andReturn($payment);

        $dialog = new PaymentDialog(self::GROUP_ID, Mockery::mock(CommandBus::class), $queryBus, $paymentService);
        $dialog->paymentId = self::PAYMENT_ID;

        $form = (new ReflectionMethod($dialog, 'createComponentForm'))->invoke($dialog);
        self::assertInstanceOf(BaseForm::class, $form);

        return $form;
    }
}
