<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use App\Model\Bank\BankTransactionAmountMismatch;
use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Entity\BankTransaction;
use App\Model\Bank\Enum\BankTransactionPairingMode;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Manager\BankTransactionPairingManager;
use App\Model\Bank\ManualBankTransactionPairingResult;
use App\Model\Bank\Repository\BankTransactionPairingRepository;
use App\Model\Bank\Transaction;
use App\Model\Invoice\Embeddable\InvoiceCustomer;
use App\Model\Invoice\Embeddable\InvoiceSupplier;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Payment\EmailTemplate;
use App\Model\Payment\EmailType;
use App\Model\Payment\Group;
use App\Model\Payment\Payment;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\VariableSymbol;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Helpers;
use Mockery as m;
use Stubs\BankAccountAccessCheckerStub;
use Stubs\OAuthsAccessCheckerStub;

final class BankTransactionPairingServiceTest extends Unit
{
    public function testManualPaymentPairingReturnsWarningsForVariableSymbolAndAccountMismatch(): void
    {
        $entityManager = $this->mockEntityManager();
        $group = $this->createGroupWithBankAccount(999);
        $payment = new Payment($group, 'Platba', [], 150.00, ChronosDate::today(), new VariableSymbol('123456'), null, null, '');
        $transaction = $this->createTransaction(123, 150.00, 654321);

        $groupRepository = m::mock(IGroupRepository::class);
        $groupRepository->shouldReceive('find')
            ->once()
            ->with($group->getId())
            ->andReturn($group);

        $pairings = m::mock(BankTransactionPairingManager::class);
        $pairings->shouldReceive('pairPaymentWithoutFlush')
            ->once()
            ->with($transaction, $payment, m::type(DateTimeImmutable::class), BankTransactionPairingMode::MANUAL, 'Tester')
            ->andReturn(true);

        $service = new BankTransactionPairingService(
            $entityManager,
            m::mock(AutomaticBankPairingService::class),
            $pairings,
            m::mock(BankTransactionPairingRepository::class),
            $groupRepository,
        );

        $result = $service->pairPaymentManually($transaction, $payment, new DateTimeImmutable('2026-03-14 10:00:00'), 'Tester');

        self::assertInstanceOf(ManualBankTransactionPairingResult::class, $result);
        self::assertSame([
            'VS bankovní transakce se liší od VS platby.',
            'Bankovní transakce pochází z jiného účtu než platba.',
        ], $result->getWarnings());
    }

    public function testManualPaymentPairingRequiresExactAmountMatch(): void
    {
        $group = $this->createGroupWithBankAccount(999);
        $payment = new Payment($group, 'Platba', [], 150.00, ChronosDate::today(), new VariableSymbol('123456'), null, null, '');
        $transaction = $this->createTransaction(999, 149.99, 123456);

        $service = new BankTransactionPairingService(
            $this->mockEntityManager(),
            m::mock(AutomaticBankPairingService::class),
            m::mock(BankTransactionPairingManager::class),
            m::mock(BankTransactionPairingRepository::class),
            m::mock(IGroupRepository::class),
        );

        $this->expectException(BankTransactionAmountMismatch::class);
        $service->pairPaymentManually($transaction, $payment, new DateTimeImmutable());
    }

    public function testCancelInvoicePairingFlushesChangesWhenManagerCancels(): void
    {
        $entityManager = $this->mockEntityManager();
        $invoice = new Invoice(
            new InvoiceSequence(1, 'INV', 2026, 'Řada'),
            new InvoiceSupplier(1, 'Dodavatel', 'Ulice', 'Praha', '11000', '12345678'),
            new InvoiceCustomer('Odběratel', 'Ulice', 'Brno', '60200', '1', '', ''),
            'Tester',
            new DateTimeImmutable('2026-03-20'),
            new DateTimeImmutable('2026-03-10'),
            new DateTimeImmutable('2026-03-10'),
            InvoicePaymentType::TRANSFER,
            null,
            'INV-1',
            new VariableSymbol('123456'),
        );

        $pairings = m::mock(BankTransactionPairingManager::class);
        $pairings->shouldReceive('cancelInvoicePairingWithoutFlush')
            ->once()
            ->with($invoice, m::type(DateTimeImmutable::class), 'Tester', 'oprava')
            ->andReturn(true);

        $service = new BankTransactionPairingService(
            $entityManager,
            m::mock(AutomaticBankPairingService::class),
            $pairings,
            m::mock(BankTransactionPairingRepository::class),
            m::mock(IGroupRepository::class),
        );

        self::assertTrue($service->cancelInvoicePairing($invoice, new DateTimeImmutable('2026-03-14 11:00:00'), 'Tester', 'oprava'));
    }

    private function mockEntityManager(): EntityManagerInterface
    {
        $entityManager = m::mock(EntityManagerInterface::class);
        $entityManager->shouldReceive('wrapInTransaction')
            ->andReturnUsing(static fn (callable $callback) => $callback());
        $entityManager->shouldReceive('flush')->byDefault();

        return $entityManager;
    }

    private function createGroupWithBankAccount(int $bankAccountId): Group
    {
        $group = new Group(
            [1],
            null,
            'Skupina',
            new Group\PaymentDefaults(null, null, null, null),
            new DateTimeImmutable(),
            [EmailType::PAYMENT_INFO => new EmailTemplate('', '')],
            null,
            m::mock(BankAccount::class, ['getId' => $bankAccountId]),
            new BankAccountAccessCheckerStub(),
            new OAuthsAccessCheckerStub(),
        );
        Helpers::assignIdentity($group, 1);

        return $group;
    }

    private function createTransaction(int $bankAccountId, float $amount, int $variableSymbol): BankTransaction
    {
        $now = new DateTimeImmutable();

        return new BankTransaction(
            m::mock(BankAccount::class, ['getId' => $bankAccountId]),
            new Transaction(
                'tx-1',
                BankTransactionSource::FIO,
                $now,
                $amount,
                '12-3456789/2010',
                'František Maša',
                $variableSymbol,
                null,
                'Poznámka',
                'src-1',
            ),
            $now,
        );
    }
}
