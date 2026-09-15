<?php

declare(strict_types=1);

namespace App\Model\Invoice\Entity;

use App\Model\Common\EmailAddress;
use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Common\Embeddable\Transaction;
use App\Model\Invoice\EmailType;
use App\Model\Invoice\Embeddable\InvoiceCustomer;
use App\Model\Invoice\Embeddable\InvoiceSupplier;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Invoice\Enum\InvoiceState;
use Brick\Math\BigDecimal;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use DateTimeImmutable;
use InvalidArgumentException;
use Nette\Utils\ArrayHash;

/**
 * Faktura — stavy, hotovostní i bankovní úhrada, e-mailová historie a splatnost.
 */
final class InvoiceTest extends Unit
{
    public function testFromFormTakesBankDataFromSequence(): void
    {
        $sequence = $this->createSequence();

        $invoice = Invoice::formForm(
            ArrayHash::from([
                'issuedBy' => 'Tester',
                'dueDate' => new DateTimeImmutable('2026-03-20'),
                'dateOfIssue' => new DateTimeImmutable('2026-03-01'),
                'paymentType' => 'TRANSFER',
            ]),
            $sequence,
            $this->createSupplier(),
            $this->createCustomer(),
        );

        self::assertSame(InvoicePaymentType::TRANSFER, $invoice->getPaymentType());
        self::assertSame('Tester', $invoice->getIssuedBy());
        self::assertSame('2026-03-01', $invoice->getDateOfTaxPayment()->format('Y-m-d'), 'DUZP se přebírá z datumu vystavení');
        self::assertNull($invoice->getAccountNumber(), 'řada bez bankovního účtu nic nepředá');
        self::assertSame(InvoiceState::ISSUED, $invoice->getState());
    }

    public function testTotalAmountSumsItems(): void
    {
        $invoice = $this->createInvoice();

        self::assertTrue($invoice->getTotalAmount()->isEqualTo(BigDecimal::zero()));

        $invoice->addItem(new InvoiceItem(BigDecimal::of('120.50'), 'Tábor', 2));
        $invoice->addItem(new InvoiceItem(BigDecimal::of('9.00'), 'Materiál'));

        self::assertSame('250.00', (string) $invoice->getTotalAmount());
        self::assertCount(2, $invoice->getItems());
    }

    public function testItemIsNotAddedTwiceAndCanBeRemoved(): void
    {
        $invoice = $this->createInvoice();
        $item = new InvoiceItem(BigDecimal::of('100'), 'Tábor');

        $invoice->addItem($item);
        $invoice->addItem($item);

        self::assertCount(1, $invoice->getItems());

        $invoice->removeItem($item);

        self::assertCount(0, $invoice->getItems());
    }

    public function testEmailRecipientsAreDeduplicated(): void
    {
        $invoice = $this->createInvoice();

        self::assertFalse($invoice->hasEmailRecipients());

        $invoice->updateEmailRecipients([
            new EmailAddress('jan@example.test'),
            new EmailAddress('eva@example.test'),
        ]);

        self::assertTrue($invoice->hasEmailRecipients());
        self::assertCount(2, $invoice->getEmailRecipients());
        self::assertSame('jan@example.test, eva@example.test', $invoice->getRecipientsString());
    }

    public function testCashPaymentRequiresReceiptNumberAndCashPaymentType(): void
    {
        $invoice = $this->createInvoice(InvoicePaymentType::CASH);

        self::assertTrue($invoice->canBePaidInCash());

        self::assertTrue($invoice->markAsPaidInCash('  P2026/1  ', new DateTimeImmutable('2026-03-05 10:00:00'), 'Hospodář'));
        self::assertSame('P2026/1', $invoice->getCashReceiptNumber());
        self::assertSame(InvoiceState::PAID, $invoice->getState());
        self::assertSame('Hospodář', $invoice->getClosedByUsername());
        self::assertSame('2026-03-05 10:00:00', $invoice->getClosedAt()?->format('Y-m-d H:i:s'));

        self::assertFalse($invoice->markAsPaidInCash('P2026/2', new DateTimeImmutable(), 'Hospodář'), 'druhá úhrada už nic nemění');
        self::assertFalse($invoice->canBePaidInCash());
        self::assertSame('Zaplaceno', $invoice->getStateLabel());
    }

    public function testCashPaymentRejectsEmptyReceiptNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Musíte zadat číslo příjmového dokladu.');

        $this->createInvoice(InvoicePaymentType::CASH)->markAsPaidInCash('   ', new DateTimeImmutable(), 'Hospodář');
    }

    public function testCashPaymentRejectsTransferInvoice(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hotovostní úhradu lze nastavit jen u faktury s formou úhrady "V hotovosti".');

        $this->createInvoice()->markAsPaidInCash('P1', new DateTimeImmutable(), 'Hospodář');
    }

    public function testBankPairingMarksInvoicePaidAndCanBeUndone(): void
    {
        $invoice = $this->createInvoice();
        $invoice->markAsDelivered(new DateTimeImmutable('2026-03-02 09:00:00'), 'Hospodář');

        self::assertTrue($invoice->pairWithBankTransaction(
            new DateTimeImmutable('2026-03-06 12:00:00'),
            'Hospodář',
            new Transaction('TX-1', '123456789/0300', 'Jan Novák', null, new ChronosDate('2026-03-06 11:00:00')),
        ));

        self::assertSame(InvoiceState::PAID, $invoice->getState());
        self::assertTrue($invoice->isPaid());
        self::assertNotNull($invoice->getTransaction());

        self::assertFalse(
            $invoice->pairWithBankTransaction(new DateTimeImmutable(), 'Hospodář', new Transaction('TX-2', '123456789/0300', 'Jan Novák', null, new ChronosDate('2026-03-06'))),
            'zaplacená faktura se znovu nepáruje',
        );

        self::assertTrue($invoice->unpairBankTransaction());
        self::assertSame(InvoiceState::DELIVERED, $invoice->getState(), 'odeslaná faktura se vrací do stavu doručená');
        self::assertNull($invoice->getClosedAt());
        self::assertFalse($invoice->unpairBankTransaction(), 'druhé odpárování nic nedělá');
    }

    public function testUnpairedInvoiceWithoutDeliveryReturnsToIssued(): void
    {
        $invoice = $this->createInvoice();
        $invoice->pairWithBankTransaction(new DateTimeImmutable(), null, new Transaction('TX-1', '123456789/0300', 'Jan Novák', null, new ChronosDate('2026-03-06')));

        $invoice->unpairBankTransaction();

        self::assertSame(InvoiceState::ISSUED, $invoice->getState());
    }

    public function testBankPairingRejectsCashInvoice(): void
    {
        $invoice = $this->createInvoice(InvoicePaymentType::CASH);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bankovní párování lze použít jen u faktury hrazené převodem.');

        $invoice->pairWithBankTransaction(new DateTimeImmutable(), null, new Transaction('TX-1', '123456789/0300', 'Jan Novák', null, new ChronosDate('2026-03-06')));
    }

    public function testUnpairingRejectsCashInvoice(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Zrušení bankovního párování lze použít jen u faktury hrazené převodem.');

        $this->createInvoice(InvoicePaymentType::CASH)->unpairBankTransaction();
    }

    public function testFilledTransactionSetterMarksInvoiceAsPaid(): void
    {
        $invoice = $this->createInvoice();

        $invoice->setTransaction(new Transaction('TX-1', '123456789/0300', 'Jan Novák', null, new ChronosDate('2026-03-06')));

        self::assertSame(InvoiceState::PAID, $invoice->getState());
    }

    public function testDeliveryIsRecordedOnlyOnce(): void
    {
        $invoice = $this->createInvoice();

        self::assertFalse($invoice->hasBeenSent());
        self::assertFalse($invoice->hasBeenDelivered());

        self::assertTrue($invoice->markAsDelivered(new DateTimeImmutable('2026-03-02 09:00:00'), 'Hospodář'));
        self::assertFalse($invoice->markAsDelivered(new DateTimeImmutable('2026-03-03 09:00:00'), 'Někdo jiný'));

        self::assertTrue($invoice->hasBeenSent());
        self::assertSame('2026-03-02 09:00:00', $invoice->getSentAt()?->format('Y-m-d H:i:s'));
        self::assertSame('Hospodář', $invoice->getSentBy());
        self::assertSame('Doručená', $invoice->getStateLabel());
    }

    public function testEmailAttemptsTrackFailuresAndDeliverInfoEmail(): void
    {
        $invoice = $this->createInvoice();
        $infoType = EmailType::get(EmailType::INVOICE_INFO);
        $reminderType = EmailType::get(EmailType::INVOICE_REMINDER);

        $invoice->recordEmailAttempt($reminderType, new DateTimeImmutable('2026-03-02 09:00:00'), 'Hospodář', false, 'SMTP je mimo');

        self::assertTrue($invoice->hasFailedEmailAttempt($reminderType));
        self::assertFalse($invoice->hasFailedEmailAttempt($infoType));
        self::assertFalse($invoice->hasBeenSent(), 'neúspěšný pokus fakturu neodešle');

        $invoice->recordSentEmail($infoType, new DateTimeImmutable('2026-03-03 09:00:00'), 'Hospodář');

        self::assertTrue($invoice->hasBeenDelivered(), 'úspěšný informační e-mail znamená doručení');
        self::assertCount(2, $invoice->getSentEmails());
    }

    public function testOverdueAndReminderDependOnDueDateAndState(): void
    {
        $invoice = $this->createInvoice();
        $afterDueDate = new DateTimeImmutable('2026-03-21');

        self::assertFalse($invoice->isOverdue(new DateTimeImmutable('2026-03-19')));
        self::assertTrue($invoice->isOverdue($afterDueDate));
        self::assertFalse($invoice->canSendReminder($afterDueDate), 'nedoručenou fakturu nemá kdo upomínat');

        $invoice->markAsDelivered(new DateTimeImmutable('2026-03-02 09:00:00'), 'Hospodář');

        self::assertTrue($invoice->canSendReminder($afterDueDate));

        $invoice->setState(InvoiceState::CANCELLED);

        self::assertFalse($invoice->isOverdue($afterDueDate), 'stornovaná faktura není po splatnosti');
        self::assertSame('Stornována', $invoice->getStateLabel());
    }

    public function testEditableOnlyWhileIssued(): void
    {
        $invoice = $this->createInvoice();

        self::assertTrue($invoice->canBeEdited());
        self::assertSame('Vystavená', $invoice->getStateLabel());

        $invoice->setState(InvoiceState::DELIVERED);

        self::assertFalse($invoice->canBeEdited());
    }

    public function testInvoiceNumberFallsBackToSequenceFormat(): void
    {
        $invoice = $this->createInvoice();
        $invoice->setInvoiceId(42);

        self::assertNull($invoice->getStoredInvoiceNumber());
        self::assertSame('FA-42/2026', $invoice->getInvoiceNumber(), 'bez uloženého čísla se skládá z řady a ID');
        self::assertSame('FA/2026', $invoice->getSequenceDisplayLabel());

        $invoice->setInvoiceNumber('FA00042');

        self::assertSame('FA00042', $invoice->getInvoiceNumber());
    }

    public function testBankDataSetters(): void
    {
        $invoice = $this->createInvoice();

        $invoice->setAccountNumber(AccountNumber::fromString('2300228890/2010'));
        $invoice->setBankName('ČSOB');
        $invoice->setIban('CZ6508000000192000145399');
        $invoice->setBic('CEKOCZPP');
        $invoice->setDateOfTaxPayment(new DateTimeImmutable('2026-03-04'));
        $invoice->setPaymentType(InvoicePaymentType::CASH);

        self::assertSame('2300228890/2010', (string) $invoice->getAccountNumber());
        self::assertSame('ČSOB', $invoice->getBankName());
        self::assertSame('CZ6508000000192000145399', $invoice->getIban());
        self::assertSame('CEKOCZPP', $invoice->getBic());
        self::assertSame('2026-03-04', $invoice->getDateOfTaxPayment()->format('Y-m-d'));
        self::assertSame(InvoicePaymentType::CASH, $invoice->getPaymentType());
    }

    private function createInvoice(InvoicePaymentType $paymentType = InvoicePaymentType::TRANSFER): Invoice
    {
        return new Invoice(
            $this->createSequence(),
            $this->createSupplier(),
            $this->createCustomer(),
            'Tester',
            new DateTimeImmutable('2026-03-20'),
            new DateTimeImmutable('2026-03-01'),
            new DateTimeImmutable('2026-03-01'),
            $paymentType,
            null,
        );
    }

    private function createSequence(): InvoiceSequence
    {
        return new InvoiceSequence(123, 'FA', 2026, 'Hlavní řada', null, null, 14, '00001');
    }

    private function createSupplier(): InvoiceSupplier
    {
        return new InvoiceSupplier(123, 'Středisko Test', 'Křižíkova 12', 'Praha', '18600', '12345678');
    }

    private function createCustomer(): InvoiceCustomer
    {
        return new InvoiceCustomer('Jan Novák', 'Ulice', 'Brno', '60200', '1', '', '');
    }
}
