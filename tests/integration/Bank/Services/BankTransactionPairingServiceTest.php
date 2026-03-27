<?php

declare(strict_types=1);

namespace App\Model\Bank\IntegrationTests;

use App\Model\Bank\BankTransactionPairingNotAllowed;
use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Entity\BankTransaction;
use App\Model\Bank\Entity\BankTransactionPairing;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Manager\BankTransactionPairingManager;
use App\Model\Bank\Repository\BankTransactionPairingRepository;
use App\Model\Bank\Services\AutomaticBankPairingService;
use App\Model\Bank\Services\BankTransactionPairingService;
use App\Model\Bank\Transaction;
use App\Model\Infrastructure\Repositories\Payment\GroupRepository;
use App\Model\Invoice\Embeddable\InvoiceCustomer;
use App\Model\Invoice\Embeddable\InvoiceSupplier;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceItem;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Payment\EmailTemplate;
use App\Model\Payment\EmailType;
use App\Model\Payment\Group;
use App\Model\Payment\IUnitResolver;
use App\Model\Payment\Payment;
use App\Model\Payment\VariableSymbol;
use Brick\Math\BigDecimal;
use Cake\Chronos\ChronosDate;
use DateTimeImmutable;
use Helpers;
use Hskauting\Tests\NullEventBus;
use IntegrationTest;
use Mockery as m;

final class BankTransactionPairingServiceTest extends IntegrationTest
{
    private BankTransactionPairingService $service;

    /** @return string[] */
    public function getTestedAggregateRoots(): array
    {
        return [
            BankAccount::class,
            BankTransaction::class,
            BankTransactionPairing::class,
            Group::class,
            Payment::class,
            InvoiceSequence::class,
            Invoice::class,
            InvoiceItem::class,
        ];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);

        parent::_before();

        $pairingRepository = new BankTransactionPairingRepository($this->entityManager);
        $pairingManager = new BankTransactionPairingManager($this->entityManager, $pairingRepository);

        $this->service = new BankTransactionPairingService(
            $this->entityManager,
            new AutomaticBankPairingService($pairingManager),
            $pairingManager,
            $pairingRepository,
            new GroupRepository($this->entityManager, new NullEventBus()),
        );
    }

    public function testSingleTransactionCannotBeManuallyPairedToPaymentAndInvoiceAtOnce(): void
    {
        $bankAccount = $this->createBankAccount();
        $payment = $this->createPayment($bankAccount, 200.00, '123456');
        $invoice = $this->createInvoice($bankAccount, 200.00, '654321');
        $transaction = $this->createTransaction($bankAccount, 200.00, 123456);

        $result = $this->service->pairPaymentManually($transaction, $payment, new DateTimeImmutable('2026-03-14 10:00:00'), 'Tester');

        self::assertSame([], $result->getWarnings());
        $this->expectException(BankTransactionPairingNotAllowed::class);
        $this->expectExceptionMessage('Bankovní transakce už je spárovaná.');

        $this->service->pairInvoiceManually($transaction, $invoice, new DateTimeImmutable('2026-03-14 10:05:00'), 'Tester');
    }

    public function testCancelledPairingFreesTransactionForAnotherManualPayment(): void
    {
        $bankAccount = $this->createBankAccount();
        $paymentOne = $this->createPayment($bankAccount, 250.00, '123456');
        $paymentTwo = $this->createPayment($bankAccount, 250.00, '987654');
        $transaction = $this->createTransaction($bankAccount, 250.00, 123456);

        $this->service->pairPaymentManually($transaction, $paymentOne, new DateTimeImmutable('2026-03-14 11:00:00'), 'Tester');
        self::assertTrue($this->service->cancelPaymentPairing($paymentOne, new DateTimeImmutable('2026-03-14 11:05:00'), 'Tester', 'oprava'));

        $result = $this->service->pairPaymentManually($transaction, $paymentTwo, new DateTimeImmutable('2026-03-14 11:10:00'), 'Tester');

        self::assertSame(['VS bankovní transakce se liší od VS platby.'], $result->getWarnings());
        /** @var list<BankTransactionPairing> $pairings */
        $pairings = $this->entityManager->getRepository(BankTransactionPairing::class)->findBy(
            ['transactionKey' => $transaction->getTransactionKey()],
            ['id' => 'ASC'],
        );

        self::assertCount(2, $pairings);

        $cancelledPairing = $pairings[0];
        self::assertSame($paymentOne->getId(), $cancelledPairing->getPayment()?->getId());
        self::assertSame('Tester', $cancelledPairing->getCancelledBy());
        self::assertSame('oprava', $cancelledPairing->getCancellationReason());
        self::assertNotNull($cancelledPairing->getCancelledAt());

        $activePairing = $pairings[1];
        self::assertSame($paymentTwo->getId(), $activePairing->getPayment()?->getId());
        self::assertNull($activePairing->getCancelledAt());
    }

    private function createBankAccount(): BankAccount
    {
        $account = new BankAccount(
            1,
            'Účet',
            Helpers::createAccountNumber(),
            'token',
            new DateTimeImmutable(),
            m::mock(IUnitResolver::class, ['getOfficialUnitId' => 1]),
        );
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        return $account;
    }

    private function createPayment(BankAccount $bankAccount, float $amount, string $variableSymbol): Payment
    {
        $group = new Group(
            [1],
            null,
            'Skupina',
            new Group\PaymentDefaults(null, null, null, null),
            new DateTimeImmutable(),
            [EmailType::PAYMENT_INFO => new EmailTemplate('', '')],
            null,
            $bankAccount,
            new \Stubs\BankAccountAccessCheckerStub(),
            new \Stubs\OAuthsAccessCheckerStub(),
        );
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        $payment = new Payment($group, 'Platba', [], $amount, ChronosDate::today(), new VariableSymbol($variableSymbol), null, null, '');
        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }

    private function createInvoice(BankAccount $bankAccount, float $amount, string $variableSymbol): Invoice
    {
        $sequence = new InvoiceSequence(1, 'INV', 2026, 'Řada', $bankAccount, null, 14);
        $sequence->setSequenceId(1);
        $this->entityManager->persist($sequence);
        $this->entityManager->flush();

        $invoice = new Invoice(
            $sequence,
            new InvoiceSupplier(1, 'Dodavatel', 'Ulice', 'Praha', '11000', '12345678'),
            new InvoiceCustomer('Odběratel', 'Ulice', 'Brno', '60200', '1', '', ''),
            'Tester',
            new DateTimeImmutable('2026-03-20'),
            new DateTimeImmutable('2026-03-10'),
            new DateTimeImmutable('2026-03-10'),
            InvoicePaymentType::TRANSFER,
            $bankAccount->getNumber(),
            'INV-1',
            new VariableSymbol($variableSymbol),
            $bankAccount->getName(),
        );
        $invoice->addItem(new InvoiceItem(BigDecimal::of((string) $amount), 'Položka'));
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        return $invoice;
    }

    private function createTransaction(BankAccount $bankAccount, float $amount, int $variableSymbol): BankTransaction
    {
        $transaction = new BankTransaction(
            $bankAccount,
            new Transaction(
                'tx-'.$variableSymbol,
                BankTransactionSource::FIO,
                new DateTimeImmutable('2026-03-14 09:00:00'),
                $amount,
                '12-3456789/2010',
                'František Maša',
                $variableSymbol,
                null,
                'Poznámka',
                'src-'.$variableSymbol,
            ),
            new DateTimeImmutable('2026-03-14 09:05:00'),
        );
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        return $transaction;
    }
}
