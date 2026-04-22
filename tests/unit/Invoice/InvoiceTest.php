<?php

declare(strict_types=1);

namespace Entity;

use App\Model\Common\EmailAddress;
use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Common\Embeddable\Transaction;
use App\Model\Invoice\EmailType;
use App\Model\Invoice\Embeddable\InvoiceCustomer;
use App\Model\Invoice\Embeddable\InvoiceSupplier;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceItem;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Invoice\Enum\InvoiceState;
use App\Model\Payment\VariableSymbol;
use Brick\Math\BigDecimal;
use Codeception\Test\Unit;
use DateTimeImmutable;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;

final class InvoiceTest extends Unit
{
    public function testInvoiceInfoEmailMarksInvoiceAsSent(): void
    {
        $invoice = $this->createInvoice(new DateTimeImmutable('2026-03-01'));
        $time = new DateTimeImmutable('2026-03-10 10:15:00');

        $invoice->recordEmailAttempt(EmailType::get(EmailType::INVOICE_INFO), $time, 'Tester');

        self::assertTrue($invoice->hasBeenSent());
        self::assertSame($time, $invoice->getSentAt());
        self::assertSame('Tester', $invoice->getSentBy());
        self::assertSame(InvoiceState::DELIVERED, $invoice->getState());
        self::assertSame('Doručená', $invoice->getStateLabel());
        self::assertCount(1, $invoice->getSentEmails());
        self::assertTrue($invoice->getSentEmails()[0]->wasSuccessful());
    }

    public function testManualDeliveryMarksInvoiceAsDeliveredWithoutEmailLog(): void
    {
        $invoice = $this->createInvoice(new DateTimeImmutable('2026-03-01'));
        $time = new DateTimeImmutable('2026-03-10 10:15:00');

        $marked = $invoice->markAsDelivered($time, 'Tester');

        self::assertTrue($marked);
        self::assertTrue($invoice->hasBeenDelivered());
        self::assertSame($time, $invoice->getSentAt());
        self::assertSame('Tester', $invoice->getSentBy());
        self::assertSame(InvoiceState::DELIVERED, $invoice->getState());
        self::assertSame('Doručená', $invoice->getStateLabel());
        self::assertCount(0, $invoice->getSentEmails());
    }

    public function testFailedInvoiceInfoEmailDoesNotMarkInvoiceAsSent(): void
    {
        $invoice = $this->createInvoice(new DateTimeImmutable('2026-03-01'));
        $time = new DateTimeImmutable('2026-03-10 10:15:00');

        $invoice->recordEmailAttempt(
            EmailType::get(EmailType::INVOICE_INFO),
            $time,
            'Tester',
            false,
            'SMTP timeout',
        );

        self::assertFalse($invoice->hasBeenSent());
        self::assertNull($invoice->getSentAt());
        self::assertTrue($invoice->hasFailedEmailAttempt(EmailType::get(EmailType::INVOICE_INFO)));
        self::assertSame('SMTP timeout', $invoice->getSentEmails()[0]->getErrorMessage());
    }

    public function testManualDeliveryIsIdempotent(): void
    {
        $invoice = $this->createInvoice(new DateTimeImmutable('2026-03-01'));

        self::assertTrue($invoice->markAsDelivered(new DateTimeImmutable('2026-03-10 10:15:00'), 'Tester'));
        self::assertFalse($invoice->markAsDelivered(new DateTimeImmutable('2026-03-10 11:00:00'), 'Jiný tester'));
        self::assertSame('Tester', $invoice->getSentBy());
    }

    public function testCashInvoiceCanBeMarkedAsPaidWithReceiptNumber(): void
    {
        $invoice = new Invoice(
            new InvoiceSequence(123, 'INV', 2026, 'Hlavní řada', null, null, 14),
            new InvoiceSupplier(123, 'Středisko Test', 'Křižíkova 12', 'Praha', '18600', '12345678'),
            new InvoiceCustomer('Jan Novák', 'Ulice', 'Brno', '60200', '1', '', ''),
            'Tester',
            new DateTimeImmutable('2026-03-20'),
            new DateTimeImmutable('2026-02-20'),
            new DateTimeImmutable('2026-02-20'),
            InvoicePaymentType::CASH,
            null,
            '2026-001',
            new VariableSymbol('123456'),
        );

        $marked = $invoice->markAsPaidInCash('PD-2026-001', new DateTimeImmutable('2026-03-10 12:00:00'), 'Pokladník');

        self::assertTrue($marked);
        self::assertTrue($invoice->isPaid());
        self::assertSame('PD-2026-001', $invoice->getCashReceiptNumber());
        self::assertSame('Pokladník', $invoice->getClosedByUsername());
        self::assertNotNull($invoice->getClosedAt());
        self::assertSame('Zaplaceno', $invoice->getStateLabel());
    }

    public function testCashInvoiceCannotBeMarkedAsPaidWithoutReceiptNumber(): void
    {
        $invoice = new Invoice(
            new InvoiceSequence(123, 'INV', 2026, 'Hlavní řada', null, null, 14),
            new InvoiceSupplier(123, 'Středisko Test', 'Křižíkova 12', 'Praha', '18600', '12345678'),
            new InvoiceCustomer('Jan Novák', 'Ulice', 'Brno', '60200', '1', '', ''),
            'Tester',
            new DateTimeImmutable('2026-03-20'),
            new DateTimeImmutable('2026-02-20'),
            new DateTimeImmutable('2026-02-20'),
            InvoicePaymentType::CASH,
            null,
            '2026-001',
            new VariableSymbol('123456'),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Musíte zadat číslo příjmového dokladu.');

        $invoice->markAsPaidInCash('   ', new DateTimeImmutable('2026-03-10 12:00:00'), 'Pokladník');
    }

    public function testReminderCanBeSentOnlyForSentOverdueInvoice(): void
    {
        $invoice = $this->createInvoice(new DateTimeImmutable('2026-03-01'));

        self::assertFalse($invoice->canSendReminder(new DateTimeImmutable('2026-03-10')));

        $invoice->recordEmailAttempt(
            EmailType::get(EmailType::INVOICE_INFO),
            new DateTimeImmutable('2026-03-02 08:00:00'),
            'Tester',
        );

        self::assertTrue($invoice->canSendReminder(new DateTimeImmutable('2026-03-10')));
        self::assertTrue($invoice->isOverdue(new DateTimeImmutable('2026-03-10')));
    }

    public function testInvoicePairedWithBankTransactionIsMarkedAsPaid(): void
    {
        $invoice = $this->createInvoice(new DateTimeImmutable('2026-03-01'));

        $paired = $invoice->pairWithBankTransaction(new DateTimeImmutable('2026-03-10 12:00:00'), 'Bank bot', new Transaction(
            'txn-123',
            '2300228890/2010',
            'Jan Novák',
            'Úhrada faktury',
            null,
        ));

        self::assertTrue($paired);
        self::assertTrue($invoice->isPaid());
        self::assertSame('Zaplaceno', $invoice->getStateLabel());
        self::assertFalse($invoice->isOverdue(new DateTimeImmutable('2026-03-10')));
        self::assertSame('Bank bot', $invoice->getClosedByUsername());
        self::assertNotNull($invoice->getClosedAt());
    }

    public function testUnpairReturnsDeliveredInvoiceBackToDeliveredState(): void
    {
        $invoice = $this->createInvoice(new DateTimeImmutable('2026-03-01'));
        $invoice->markAsDelivered(new DateTimeImmutable('2026-03-02 08:00:00'), 'Tester');

        $invoice->pairWithBankTransaction(new DateTimeImmutable('2026-03-10 12:00:00'), 'Bank bot', new Transaction(
            'txn-123',
            '2300228890/2010',
            'Jan Novák',
            'Úhrada faktury',
            null,
        ));

        self::assertTrue($invoice->unpairBankTransaction());
        self::assertSame(InvoiceState::DELIVERED, $invoice->getState());
        self::assertSame('Doručená', $invoice->getStateLabel());
    }

    public function testEmptyTransactionDoesNotMarkInvoiceAsPaid(): void
    {
        $invoice = $this->createInvoice(new DateTimeImmutable('2026-03-20'));

        $transaction = (new ReflectionClass(Transaction::class))->newInstanceWithoutConstructor();
        $invoice->setTransaction($transaction);

        self::assertFalse($invoice->isPaid());
        self::assertSame('issued', $invoice->getState());
        self::assertSame('Vystavená', $invoice->getStateLabel());
    }

    public function testIssuedInvoiceUsesIssuedStateLabel(): void
    {
        $invoice = $this->createInvoice(new DateTimeImmutable('2026-03-20'));

        self::assertSame('Vystavená', $invoice->getStateLabel());
    }

    public function testTotalAmountIsSumOfAllItems(): void
    {
        $invoice = $this->createInvoice(new DateTimeImmutable('2026-03-20'));
        $invoice->addItem(new InvoiceItem(BigDecimal::of('100.00'), 'Položka A'));
        $invoice->addItem(new InvoiceItem(BigDecimal::of('50.00'), 'Položka B', 2));

        self::assertSame('200.00', (string) $invoice->getTotalAmount());
    }

    public function testLegacyInvoiceNumberFallbackUsesSequenceAndYear(): void
    {
        $invoice = $this->createInvoice(new DateTimeImmutable('2026-03-20'));
        $invoice->setInvoiceId(42);

        $reflection = new ReflectionProperty($invoice, 'invoiceNumber');
        $reflection->setValue($invoice, null);

        self::assertSame('INV-42/2026', $invoice->getInvoiceNumber());
    }

    public function testStoredInvoiceNumberTakesPrecedenceOverLegacyFallback(): void
    {
        $invoice = $this->createInvoice(new DateTimeImmutable('2026-03-20'));
        $invoice->setInvoiceId(42);

        self::assertSame('2026-001', $invoice->getInvoiceNumber());
    }

    public function testEmailRecipientsAreStoredUniquely(): void
    {
        $invoice = $this->createInvoice(new DateTimeImmutable('2026-03-20'));
        $invoice->updateEmailRecipients([
            new EmailAddress('first@example.test'),
            new EmailAddress('first@example.test'),
            new EmailAddress('second@example.test'),
        ]);

        self::assertCount(2, $invoice->getEmailRecipients());
        self::assertSame(
            ['first@example.test', 'second@example.test'],
            array_map(static fn (EmailAddress $email): string => $email->getValue(), $invoice->getEmailRecipients()),
        );
    }

    public function testCustomerDisplayNameFallsBackForAnonymousCustomer(): void
    {
        $invoice = new Invoice(
            new InvoiceSequence(123, 'INV', 2026, 'Hlavní řada', null, null, 14),
            new InvoiceSupplier(123, 'Středisko Test', 'Křižíkova 12', 'Praha', '18600', '12345678', '+420123456789'),
            new InvoiceCustomer('', '', '', '', '', '', ''),
            'Tester',
            new DateTimeImmutable('2026-03-20'),
            new DateTimeImmutable('2026-02-20'),
            new DateTimeImmutable('2026-02-20'),
            InvoicePaymentType::TRANSFER,
            AccountNumber::fromString('2300228890/2010'),
            '2026-001',
            new VariableSymbol('123456'),
        );

        self::assertSame('Bez identifikace odběratele', $invoice->getCustomerDisplayName());
        self::assertSame('', $invoice->getCustomer()->getDisplayAddress());
    }

    public function testLegacyInvoiceDataRemainRenderableInList(): void
    {
        $invoice = $this->createInvoice(new DateTimeImmutable('2026-03-20'));
        $invoice->setInvoiceId(42);

        $reflection = new ReflectionProperty($invoice, 'invoiceNumber');
        $reflection->setValue($invoice, null);

        self::assertSame('INV-42/2026', $invoice->getInvoiceNumber());
        self::assertFalse($invoice->hasEmailRecipients());
        self::assertFalse($invoice->hasBeenSent());
        self::assertFalse($invoice->canSendReminder(new DateTimeImmutable('2026-03-10')));
        self::assertFalse($invoice->hasFailedEmailAttempt(EmailType::get(EmailType::INVOICE_INFO)));
    }

    public function testCashInvoiceCanExistWithoutBankAccountDetails(): void
    {
        $invoice = new Invoice(
            new InvoiceSequence(123, 'INV', 2026, 'Hlavní řada', null, null, 14),
            new InvoiceSupplier(123, 'Středisko Test', 'Křižíkova 12', 'Praha', '18600', '12345678'),
            new InvoiceCustomer('Jan Novák', 'Ulice', 'Brno', '60200', '1', '', ''),
            'Tester',
            new DateTimeImmutable('2026-03-20'),
            new DateTimeImmutable('2026-02-20'),
            new DateTimeImmutable('2026-02-20'),
            InvoicePaymentType::CASH,
            null,
            '2026-001',
            new VariableSymbol('123456'),
        );

        self::assertNull($invoice->getAccountNumber());
        self::assertNull($invoice->getBankName());
        self::assertNull($invoice->getIban());
        self::assertNull($invoice->getBic());
        self::assertSame(InvoicePaymentType::CASH, $invoice->getPaymentType());
    }

    private function createInvoice(DateTimeImmutable $dueDate): Invoice
    {
        return new Invoice(
            new InvoiceSequence(123, 'INV', 2026, 'Hlavní řada', null, null, 14),
            new InvoiceSupplier(123, 'Středisko Test', 'Křižíkova 12', 'Praha', '18600', '12345678', '+420123456789'),
            new InvoiceCustomer('Odběratel', 'Ulice', 'Brno', '60200', '1', '2', '87654321'),
            'Tester',
            $dueDate,
            new DateTimeImmutable('2026-02-20'),
            new DateTimeImmutable('2026-02-20'),
            InvoicePaymentType::TRANSFER,
            AccountNumber::fromString('2300228890/2010'),
            '2026-001',
            new VariableSymbol('123456'),
        );
    }
}
