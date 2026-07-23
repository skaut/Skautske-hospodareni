<?php

declare(strict_types=1);

namespace Tests\Integration\BankAccountDetail;

use App\Components\Payment\BankAccountDetail\BankAccountDetailViewFactory;
use App\Components\Payment\BankAccountDetail\BankAccountManualPairingService;
use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Entity\BankTransaction;
use App\Model\Bank\Entity\BankTransactionPairing;
use App\Model\Bank\Enum\BankTransactionPairingMode;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Manager\BankTransactionPairingManager;
use App\Model\Bank\Repository\BankTransactionPairingRepository;
use App\Model\Bank\Repository\BankTransactionRepository;
use App\Model\Bank\Services\AutomaticBankPairingService;
use App\Model\Bank\Services\BankPairingCandidateProvider;
use App\Model\Bank\Services\BankTransactionPairingService;
use App\Model\Bank\Transaction;
use App\Model\Infrastructure\Repositories\Payment\GroupRepository;
use App\Model\Infrastructure\Repositories\Payment\PaymentRepository;
use App\Model\Invoice\Embeddable\InvoiceCustomer;
use App\Model\Invoice\Embeddable\InvoiceSupplier;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceItem;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Payment\BankAccountService;
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
use InvalidArgumentException;
use Mockery as m;
use Nette\Application\LinkGenerator;
use Nette\Application\Routers\SimpleRouter;
use Nette\Http\UrlScript;

final class BankAccountManualPairingServiceTest extends IntegrationTest
{
    private BankAccountManualPairingService $service;

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
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

        $this->service = new BankAccountManualPairingService(
            new BankTransactionRepository($this->entityManager),
            new PaymentRepository($this->entityManager, new NullEventBus()),
            new InvoiceRepository($this->entityManager),
            new BankTransactionPairingService(
                $this->entityManager,
                new AutomaticBankPairingService($pairingManager),
                $pairingManager,
                $pairingRepository,
                new GroupRepository($this->entityManager, new NullEventBus()),
            ),
        );
    }

    public function testSubunitCannotManuallyPairSiblingPaymentOrInvoice(): void
    {
        $bankAccount = $this->createBankAccount();
        $accessibleGroup = $this->createGroup(11, $bankAccount, 'Muj oddil');
        $siblingGroup = $this->createGroup(12, $bankAccount, 'Sourozenec');
        $siblingPayment = $this->createPayment($siblingGroup, 200.00, '200001');
        $siblingSequence = $this->createSequence(12, $bankAccount, 1);
        $siblingInvoice = $this->createInvoice($siblingSequence, $bankAccount, 'FA120001', '300001', '300.00');

        $paymentTransaction = $this->createTransaction($bankAccount, 200.00, 200001);
        $invoiceTransaction = $this->createTransaction($bankAccount, 300.00, 300001);

        try {
            $this->service->pairTransactionToPayment(
                $bankAccount->getId(),
                $paymentTransaction->getTransactionKey(),
                $siblingPayment->getId(),
                [$accessibleGroup->getId()],
                'Tester',
            );
            self::fail('Sibling payment pairing should be blocked.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame('Platbu nelze v tomto scope bankovního účtu párovat.', $exception->getMessage());
        }

        try {
            $this->service->pairTransactionToInvoice(
                $bankAccount->getId(),
                $invoiceTransaction->getTransactionKey(),
                $siblingInvoice->getId(),
                [11],
                'Tester',
            );
            self::fail('Sibling invoice pairing should be blocked.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame('Faktura nebyla nalezena.', $exception->getMessage());
        }

        self::assertNull($this->entityManager->getRepository(BankTransactionPairing::class)->findOneBy([
            'transactionKey' => $paymentTransaction->getTransactionKey(),
        ]));
        self::assertNull($this->entityManager->getRepository(BankTransactionPairing::class)->findOneBy([
            'transactionKey' => $invoiceTransaction->getTransactionKey(),
        ]));
    }

    public function testManualPairingAllowsItemsWithinAccessibleScope(): void
    {
        $bankAccount = $this->createBankAccount();
        $group = $this->createGroup(11, $bankAccount, 'Oddil');
        $payment = $this->createPayment($group, 200.00, '200001');
        $sequence = $this->createSequence(11, $bankAccount, 1);
        $invoice = $this->createInvoice($sequence, $bankAccount, 'FA110001', '300001', '300.00');

        $paymentTransaction = $this->createTransaction($bankAccount, 200.00, 200001);
        $invoiceTransaction = $this->createTransaction($bankAccount, 300.00, 300001);

        $paymentOutcome = $this->service->pairTransactionToPayment(
            $bankAccount->getId(),
            $paymentTransaction->getTransactionKey(),
            $payment->getId(),
            [$group->getId()],
            'Tester',
        );
        self::assertSame('Bankovní transakce byla ručně spárována s platbou.', $paymentOutcome->successMessage);
        self::assertSame([], $paymentOutcome->warnings);

        $invoiceOutcome = $this->service->pairTransactionToInvoice(
            $bankAccount->getId(),
            $invoiceTransaction->getTransactionKey(),
            $invoice->getId(),
            [10, 11],
            'Tester',
        );
        self::assertSame('Bankovní transakce byla ručně spárována s fakturou.', $invoiceOutcome->successMessage);
        self::assertSame([], $invoiceOutcome->warnings);

        self::assertNotNull($payment->getTransaction());
        self::assertTrue($invoice->isPaid());
        self::assertNotNull($invoice->getTransaction());
    }

    public function testDetailDoesNotOfferManualCandidatesForPairedTransactionAndAllowsNoVariableSymbolByAmount(): void
    {
        $bankAccount = $this->createBankAccount();
        $group = $this->createGroup(11, $bankAccount, 'Oddil');
        $amountCandidate = $this->createPayment($group, 150.00, '150001');
        $pairedPayment = $this->createPayment($group, 200.00, '200001');
        $withoutVariableSymbol = $this->createTransaction($bankAccount, 150.00, null);
        $pairedTransaction = $this->createTransaction($bankAccount, 200.00, 200001);
        $activePairing = BankTransactionPairing::forPayment(
            $pairedTransaction,
            $pairedTransaction->getTransactionKey(),
            $pairedPayment,
            BankTransactionPairingMode::MANUAL,
            new DateTimeImmutable('2026-03-14 12:00:00'),
            'Tester',
            $bankAccount->getId(),
            $bankAccount->getName(),
            (string) $bankAccount->getNumber(),
            $bankAccount->getNumber()->getBankCode(),
        );

        $accounts = m::mock(BankAccountService::class);
        $accounts->shouldReceive('getPersistentTransactions')
            ->once()
            ->with($bankAccount->getId(), 60)
            ->andReturn([$withoutVariableSymbol, $pairedTransaction]);
        $accounts->shouldReceive('getImportBatches')
            ->once()
            ->with($bankAccount->getId())
            ->andReturn([]);

        $pairingCandidates = m::mock(BankPairingCandidateProvider::class);
        $pairingCandidates->shouldReceive('getDomainCandidatesForBankAccount')
            ->once()
            ->with($bankAccount->getId())
            ->andReturn([]);

        $pairings = m::mock(BankTransactionPairingRepository::class);
        $pairings->shouldReceive('findActiveByTransactionKeys')
            ->once()
            ->with([$withoutVariableSymbol->getTransactionKey(), $pairedTransaction->getTransactionKey()])
            ->andReturn([$activePairing]);

        $factory = new BankAccountDetailViewFactory(
            $accounts,
            $pairingCandidates,
            $pairings,
            new PaymentRepository($this->entityManager, new NullEventBus()),
            new InvoiceRepository($this->entityManager),
            new GroupRepository($this->entityManager, new NullEventBus()),
            new LinkGenerator(new SimpleRouter(), new UrlScript('https://example.test/')),
        );

        $detail = $factory->create($bankAccount->getId(), [(int) $group->getId() => $group->getName()], [11], includeInvoices: false);

        self::assertNotNull($detail->transactionRows);
        self::assertSame('payment:'.$amountCandidate->getId(), $detail->transactionRows[0]->manualCandidates[0]->targetKey);
        self::assertNull($detail->transactionRows[0]->conflictReason);
        self::assertSame([], $detail->transactionRows[1]->manualCandidates);
        self::assertNotNull($detail->transactionRows[1]->pairing);
    }

    private function createBankAccount(): BankAccount
    {
        $account = new BankAccount(
            10,
            'Strediskovy ucet',
            Helpers::createAccountNumber(),
            'token',
            new DateTimeImmutable('2026-03-14 08:00:00'),
            m::mock(IUnitResolver::class, ['getOfficialUnitId' => 10]),
        );
        $account->allowForSubunits();
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        return $account;
    }

    private function createGroup(int $unitId, BankAccount $bankAccount, string $name): Group
    {
        $group = new Group(
            [$unitId],
            null,
            $name,
            new Group\PaymentDefaults(null, null, null, null),
            new DateTimeImmutable('2026-03-14 09:00:00'),
            [EmailType::PAYMENT_INFO => new EmailTemplate('', '')],
            null,
            $bankAccount,
            new \Stubs\BankAccountAccessCheckerStub(),
            new \Stubs\OAuthsAccessCheckerStub(),
        );
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        return $group;
    }

    private function createPayment(Group $group, float $amount, string $variableSymbol): Payment
    {
        $payment = new Payment(
            $group,
            'Platba '.$variableSymbol,
            [],
            $amount,
            new ChronosDate('2026-03-20'),
            new VariableSymbol($variableSymbol),
            null,
            null,
            '',
        );
        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }

    private function createSequence(int $unitId, BankAccount $bankAccount, int $sequenceId): InvoiceSequence
    {
        $sequence = new InvoiceSequence($unitId, 'FA', 2026, 'Faktury', $bankAccount, null, 14, '00001');
        $sequence->setSequenceId($sequenceId);
        $this->entityManager->persist($sequence);
        $this->entityManager->flush();

        return $sequence;
    }

    private function createInvoice(
        InvoiceSequence $sequence,
        BankAccount $bankAccount,
        string $invoiceNumber,
        string $variableSymbol,
        string $amount,
    ): Invoice {
        $invoice = new Invoice(
            $sequence,
            new InvoiceSupplier(10, 'Dodavatel', 'Ulice', 'Praha', '11000', '12345678'),
            new InvoiceCustomer('Odběratel', 'Ulice', 'Brno', '60200', '1', '', ''),
            'Tester',
            new DateTimeImmutable('2026-03-20'),
            new DateTimeImmutable('2026-03-10'),
            new DateTimeImmutable('2026-03-10'),
            InvoicePaymentType::TRANSFER,
            $bankAccount->getNumber(),
            null,
            null,
            $bankAccount->getName(),
        );
        $invoice->addItem(new InvoiceItem(BigDecimal::of($amount), 'Položka'));
        $invoice->assignNumbering((int) $variableSymbol, $invoiceNumber, new VariableSymbol($variableSymbol));
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        return $invoice;
    }

    private function createTransaction(BankAccount $bankAccount, float $amount, ?int $variableSymbol): BankTransaction
    {
        $transaction = new BankTransaction(
            $bankAccount,
            new Transaction(
                'tx-'.$variableSymbol.'-'.(string) $amount,
                BankTransactionSource::FIO,
                new DateTimeImmutable('2026-03-14 10:00:00'),
                $amount,
                '12-3456789/2010',
                'Frantisek Masa',
                $variableSymbol,
                null,
                'Poznamka',
                'src-'.$variableSymbol.'-'.(string) $amount,
            ),
            new DateTimeImmutable('2026-03-14 10:05:00'),
        );
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        return $transaction;
    }
}
