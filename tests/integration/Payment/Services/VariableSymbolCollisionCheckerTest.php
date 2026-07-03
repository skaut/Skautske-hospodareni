<?php

declare(strict_types=1);

namespace App\Model\Payment\Services;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Infrastructure\Repositories\Payment\PaymentRepository;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceItem;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Payment\EmailTemplate;
use App\Model\Payment\EmailType;
use App\Model\Payment\Group;
use App\Model\Payment\Payment;
use App\Model\Payment\VariableSymbol;
use App\Model\Payment\VariableSymbolCollision;
use BankingFixtures;
use DateTimeImmutable;
use Hskauting\Tests\NullEventBus;
use IntegrationTest;
use Stubs\BankAccountAccessCheckerStub;
use Stubs\OAuthsAccessCheckerStub;

final class VariableSymbolCollisionCheckerTest extends IntegrationTest
{
    use BankingFixtures;

    private VariableSymbolCollisionChecker $checker;

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [
            BankAccount::class,
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

        $this->checker = new VariableSymbolCollisionChecker(
            new PaymentRepository($this->entityManager, new NullEventBus()),
            new InvoiceRepository($this->entityManager),
        );
    }

    public function testInvoiceVariableSymbolCollidesWithOpenPaymentOnSameAccount(): void
    {
        $bankAccount = $this->createBankAccount();
        $group = $this->createGroup($bankAccount);
        $this->createPayment($group, '123456');

        $sequence = $this->createSequence($bankAccount, 1);
        $invoice = $this->createInvoice($sequence, $bankAccount, 'FA123456', '123456');

        $this->expectException(VariableSymbolCollision::class);
        $this->expectExceptionMessage(
            'Variabilní symbol 123456 je už použitý u jiné otevřené bankovní platby nebo faktury na stejném účtu.',
        );

        $this->checker->assertUniqueForInvoice($invoice, new VariableSymbol('123456'));
    }

    public function testPaymentVariableSymbolCollidesWithOpenInvoiceOnSameAccount(): void
    {
        $bankAccount = $this->createBankAccount();
        $sequence = $this->createSequence($bankAccount, 1);
        $this->persistInvoice($this->createInvoice($sequence, $bankAccount, 'FA123456', '123456'));

        $group = $this->createGroup($bankAccount);

        $this->expectException(VariableSymbolCollision::class);
        $this->expectExceptionMessage(
            'Variabilní symbol 123456 je už použitý u jiné otevřené bankovní platby nebo faktury na stejném účtu.',
        );

        $this->checker->assertUniqueForPayment($group, null, new VariableSymbol('123456'));
    }

    private function createBankAccount(): BankAccount
    {
        $account = $this->createBankAccountFixture(token: 'token');
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        return $account;
    }

    private function createGroup(BankAccount $bankAccount): Group
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
            new BankAccountAccessCheckerStub(),
            new OAuthsAccessCheckerStub(),
        );
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        return $group;
    }

    private function createPayment(Group $group, string $variableSymbol): Payment
    {
        $payment = new Payment(
            $group,
            'Platba',
            [],
            150.00,
            new \Cake\Chronos\ChronosDate('2026-03-20'),
            new VariableSymbol($variableSymbol),
            null,
            null,
            '',
        );
        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }

    private function createSequence(BankAccount $bankAccount, int $sequenceId): InvoiceSequence
    {
        $sequence = $this->createInvoiceSequenceFixture($bankAccount, $sequenceId);
        $this->entityManager->persist($sequence);
        $this->entityManager->flush();

        return $sequence;
    }

    private function createInvoice(InvoiceSequence $sequence, BankAccount $bankAccount, string $invoiceNumber, string $variableSymbol): Invoice
    {
        return $this->createInvoiceFixture($sequence, $bankAccount, $invoiceNumber, $variableSymbol, '150.00', InvoicePaymentType::TRANSFER, false);
    }

    private function persistInvoice(Invoice $invoice): void
    {
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();
    }
}
