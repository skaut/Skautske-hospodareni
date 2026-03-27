<?php

declare(strict_types=1);

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Transaction;
use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Invoice\Embeddable\InvoiceCustomer;
use App\Model\Invoice\Embeddable\InvoiceSupplier;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceItem;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Payment\Group;
use App\Model\Payment\IUnitResolver;
use App\Model\Payment\VariableSymbol;
use Brick\Math\BigDecimal;
use Mockery as m;
use Stubs\BankAccountAccessCheckerStub;
use Stubs\OAuthsAccessCheckerStub;

trait BankingFixtures
{
    protected function createBankAccountFixture(
        string $name = 'Hlavní účet',
        ?string $token = 'test-token',
        BankTransactionSource $source = BankTransactionSource::FIO,
        int $unitId = 1,
    ): BankAccount {
        return new BankAccount(
            $unitId,
            $name,
            new AccountNumber(null, '2000942144', '2010'),
            $token,
            new DateTimeImmutable(),
            m::mock(IUnitResolver::class, ['getOfficialUnitId' => $unitId]),
            $source,
        );
    }

    /**
     * @param int[] $unitIds
     */
    protected function createPaymentGroupFixture(?BankAccount $bankAccount, string $name = 'Test', array $unitIds = [1]): Group
    {
        return new Group(
            $unitIds,
            null,
            $name,
            new Group\PaymentDefaults(null, null, null, null),
            new DateTimeImmutable(),
            Helpers::createEmails(),
            null,
            $bankAccount,
            new BankAccountAccessCheckerStub(),
            new OAuthsAccessCheckerStub(),
        );
    }

    protected function createFioTransactionFixture(int $transactionId, float $amount, ?string $variableSymbol, ?string $note = 'Poznámka'): Transaction
    {
        return new Transaction(
            (string) $transactionId,
            BankTransactionSource::FIO,
            new DateTimeImmutable(),
            $amount,
            '',
            'Payer',
            $variableSymbol === null || $variableSymbol === '' ? null : (int) $variableSymbol,
            null,
            $note,
            (string) ($transactionId + 1000),
        );
    }

    protected function createInvoiceSequenceFixture(
        BankAccount $bankAccount,
        int $sequenceId,
        string $sequencePrefix = 'FA12345',
        bool $automaticPairingEnabled = false,
        ?int $pairingDaysBack = null,
    ): InvoiceSequence {
        $sequence = new InvoiceSequence(1, $sequencePrefix, 2026, 'Faktury', $bankAccount, null, 14);
        $sequence->setSequenceId($sequenceId);
        $sequence->setAutomaticPairingEnabled($automaticPairingEnabled);

        if ($pairingDaysBack !== null) {
            $sequence->setPairingDaysBack($pairingDaysBack);
        }

        return $sequence;
    }

    protected function createInvoiceFixture(
        InvoiceSequence $sequence,
        BankAccount $bankAccount,
        string $invoiceNumber,
        string $variableSymbol,
        string $amount = '150.00',
        InvoicePaymentType $paymentType = InvoicePaymentType::TRANSFER,
        bool $assignNumbering = true,
    ): Invoice {
        $invoice = new Invoice(
            $sequence,
            new InvoiceSupplier(1, 'Dodavatel', 'Ulice', 'Praha', '11000', '12345678'),
            new InvoiceCustomer('Odběratel', 'Ulice', 'Brno', '60200', '1', '', ''),
            'Tester',
            new DateTimeImmutable('2026-03-20'),
            new DateTimeImmutable('2026-03-10'),
            new DateTimeImmutable('2026-03-10'),
            $paymentType,
            $bankAccount->getNumber(),
            $assignNumbering ? null : $invoiceNumber,
            $assignNumbering ? null : new VariableSymbol($variableSymbol),
            $bankAccount->getName(),
        );
        $invoice->addItem(new InvoiceItem(BigDecimal::of($amount), 'Položka'));

        if ($assignNumbering) {
            $invoice->assignNumbering((int) $variableSymbol, $invoiceNumber, new VariableSymbol($variableSymbol));
        }

        return $invoice;
    }
}
