<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Entity\BankTransaction;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Manager\BankTransactionPairingManager;
use App\Model\Bank\PairingCandidate;
use App\Model\Bank\Transaction;
use App\Model\Invoice\Embeddable\InvoiceCustomer;
use App\Model\Invoice\Embeddable\InvoiceSupplier;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceItem;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Payment\EmailTemplate;
use App\Model\Payment\EmailType;
use App\Model\Payment\Group;
use App\Model\Payment\Payment;
use App\Model\Payment\VariableSymbol;
use Brick\Math\BigDecimal;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Helpers;
use Mockery as m;
use Stubs\BankAccountAccessCheckerStub;
use Stubs\OAuthsAccessCheckerStub;

final class AutomaticBankPairingServiceTest extends Unit
{
    public function testAutomaticPairingSkipsDomainAmbiguityAcrossPaymentsAndInvoices(): void
    {
        $transaction = $this->persistentTransaction('tx-1', 150.00, '123456');
        $payment = $this->createPayment(150.00, '123456');
        $invoice = $this->createInvoice(150.00, '123456');

        $pairings = m::mock(BankTransactionPairingManager::class);
        $pairings->shouldNotReceive('pairPaymentWithoutFlush');
        $pairings->shouldNotReceive('pairInvoiceWithoutFlush');

        $result = (new AutomaticBankPairingService($pairings))->pair(
            [$transaction],
            [PairingCandidate::forPayment($payment), PairingCandidate::forInvoice($invoice)],
            [PairingCandidate::forPayment($payment)],
            new DateTimeImmutable(),
        );

        self::assertSame([], $result['payments']);
        self::assertSame([], $result['invoices']);
    }

    public function testAutomaticPairingSkipsDuplicateTransactionsForSameCandidate(): void
    {
        $payment = $this->createPayment(150.00, '123456');

        $pairings = m::mock(BankTransactionPairingManager::class);
        $pairings->shouldNotReceive('pairPaymentWithoutFlush');

        $result = (new AutomaticBankPairingService($pairings))->pair(
            [
                $this->persistentTransaction('tx-1', 150.00, '123456'),
                $this->persistentTransaction('tx-2', 150.00, '123456'),
            ],
            [PairingCandidate::forPayment($payment)],
            [PairingCandidate::forPayment($payment)],
            new DateTimeImmutable(),
        );

        self::assertSame([], $result['payments']);
        self::assertSame([], $result['invoices']);
    }

    public function testAutomaticPairingPairsUniqueInvoiceCandidate(): void
    {
        $invoice = $this->createInvoice(150.00, '123456');
        $transaction = $this->persistentTransaction('tx-1', 150.00, '123456');

        $pairings = m::mock(BankTransactionPairingManager::class);
        $pairings->shouldReceive('pairInvoiceWithoutFlush')
            ->once()
            ->with($transaction, $invoice, m::type(DateTimeImmutable::class), \App\Model\Bank\Enum\BankTransactionPairingMode::AUTOMATIC, null)
            ->andReturn(true);

        $result = (new AutomaticBankPairingService($pairings))->pair(
            [$transaction],
            [PairingCandidate::forInvoice($invoice)],
            [PairingCandidate::forInvoice($invoice)],
            new DateTimeImmutable(),
        );

        self::assertSame([], $result['payments']);
        self::assertSame([$invoice], $result['invoices']);
    }

    private function createPayment(float $amount, string $variableSymbol): Payment
    {
        $group = new Group(
            [1],
            null,
            'Skupina',
            new Group\PaymentDefaults(null, null, null, null),
            new DateTimeImmutable(),
            [EmailType::PAYMENT_INFO => new EmailTemplate('', '')],
            null,
            m::mock(BankAccount::class, ['getId' => 1]),
            new BankAccountAccessCheckerStub(),
            new OAuthsAccessCheckerStub(),
        );
        Helpers::assignIdentity($group, 1);

        return new Payment($group, 'Platba', [], $amount, ChronosDate::today(), new VariableSymbol($variableSymbol), null, null, '');
    }

    private function createInvoice(float $amount, string $variableSymbol): Invoice
    {
        $bankAccount = new BankAccount(
            1,
            'Účet',
            Helpers::createAccountNumber(),
            'token',
            new DateTimeImmutable(),
            m::mock(\App\Model\Payment\IUnitResolver::class, ['getOfficialUnitId' => 1]),
        );
        Helpers::assignIdentity($bankAccount, 1);

        $sequence = new InvoiceSequence(1, 'INV', 2026, 'Řada', $bankAccount, null, 14);
        Helpers::assignIdentity($sequence, 1);

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
            'INV-2026-001',
            new VariableSymbol($variableSymbol),
            $bankAccount->getName(),
        );
        Helpers::assignIdentity($invoice, 1);
        $invoice->addItem(new InvoiceItem(BigDecimal::of((string) $amount), 'Položka'));

        return $invoice;
    }

    private function persistentTransaction(string $id, float $amount, string $variableSymbol): BankTransaction
    {
        $today = new DateTimeImmutable();

        return new BankTransaction(
            m::mock(BankAccount::class),
            new Transaction(
                $id,
                BankTransactionSource::FIO,
                $today,
                $amount,
                '12-3456789/2010',
                'František Maša',
                (new VariableSymbol($variableSymbol))->toInt(),
                null,
                'Poznámka',
                $id,
            ),
            $today,
        );
    }
}
