<?php

declare(strict_types=1);

namespace Tests\Integration\Invoice;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Entity\BankTransaction;
use App\Model\Bank\Entity\BankTransactionPairing;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\InvoiceBankService;
use App\Model\Bank\Transaction;
use App\Model\Invoice\Embeddable\InvoiceCustomer;
use App\Model\Invoice\Embeddable\InvoiceSupplier;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceItem;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Payment\FioClientStub;
use App\Model\Payment\Group;
use App\Model\Payment\Payment;
use App\Model\Payment\Repositories\IBankAccountRepository;
use App\Model\Payment\VariableSymbol;
use BankingFixtures;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use IntegrationTest;
use IntegrationTester;

class InvoiceBankServiceTest extends IntegrationTest
{
    use BankingFixtures;

    /**
     * @var IntegrationTester
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $tester;

    private InvoiceBankService $invoiceBankService;

    private IBankAccountRepository $bankAccounts;

    private InvoiceRepository $invoiceRepository;

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['Invoice/InvoiceBankServiceTest.neon']);

        parent::_before();

        $this->invoiceBankService = $this->tester->grabService(InvoiceBankService::class);
        $this->bankAccounts = $this->tester->grabService(IBankAccountRepository::class);
        $this->entityManager = $this->tester->grabService(EntityManager::class);
        $this->invoiceRepository = $this->tester->grabService(InvoiceRepository::class);
    }

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [
            BankAccount::class,
            BankTransaction::class,
            BankTransactionPairing::class,
            InvoiceSequence::class,
            Invoice::class,
            InvoiceItem::class,
            Group::class,
            Payment::class,
        ];
    }

    public function testPairSequences(): void
    {
        $bankAccount = $this->createBankAccountFixture();
        $this->bankAccounts->save($bankAccount);

        $sequence = new InvoiceSequence(1, 'FA12345', 2026, 'Faktury', $bankAccount, null, 14, '6');
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
            null,
            null,
            $bankAccount->getName(),
        );
        $invoice->addItem(new InvoiceItem(BigDecimal::of('150.00'), 'Položka'));
        $invoice->assignNumbering(6, 'FA123456', new VariableSymbol('123456'));
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->tester->grabService(FioClientStub::class)
            ->setTransactions([
                $this->createFioTransactionFixture(1, 150.00, '123456'),
            ]);

        $pairedCount = $this->invoiceBankService->pairAllSequences([$sequence->getId()], 7);

        $pairedInvoice = $this->invoiceRepository->findOrFail($invoice->getId());
        self::assertSame(1, $pairedCount);
        self::assertTrue($pairedInvoice->isPaid());
        self::assertNotNull($pairedInvoice->getClosedAt());
        self::assertNotNull($pairedInvoice->getTransaction());
        self::assertSame('1', $pairedInvoice->getTransaction()->getId());
        $pairing = $this->entityManager->getRepository(BankTransactionPairing::class)->findOneBy([
            'invoice' => $invoice,
            'transactionKey' => '1',
        ]);
        self::assertInstanceOf(BankTransactionPairing::class, $pairing);
        self::assertSame('automatic', $pairing->getPairingMode()->value);
    }

    public function testPairSequencesWithLongTransactionKey(): void
    {
        $bankAccount = $this->createBankAccountFixture();
        $this->bankAccounts->save($bankAccount);

        $sequence = new InvoiceSequence(1, 'FA12345', 2026, 'Faktury', $bankAccount, null, 14, '6');
        $sequence->setSequenceId(1);
        $this->entityManager->persist($sequence);
        $this->entityManager->flush();

        $invoice = $this->createInvoice($sequence, $bankAccount, 'FA123456', '123456', '150.00');

        $this->entityManager->persist(new BankTransaction(
            $bankAccount,
            new Transaction(
                'gpc:33a0e9641c4ccabc6e189f0be182c1aec44d51f87c36899402eae37a8da8',
                BankTransactionSource::GPC,
                new DateTimeImmutable(),
                150.00,
                '',
                'Payer',
                123456,
                null,
                'Poznamka',
                null,
            ),
            new DateTimeImmutable(),
        ));
        $this->entityManager->flush();

        self::assertSame(1, $this->invoiceBankService->pairAllSequences([$sequence->getId()], 7));

        $pairedInvoice = $this->invoiceRepository->findOrFail($invoice->getId());
        self::assertTrue($pairedInvoice->isPaid());
        self::assertSame(
            'gpc:33a0e9641c4ccabc6e189f0be182c1aec44d51f87c36899402eae37a8da8',
            $pairedInvoice->getTransaction()?->getId(),
        );
    }

    public function testCashInvoiceIsIgnoredByBankPairing(): void
    {
        $bankAccount = $this->createBankAccountFixture();
        $this->bankAccounts->save($bankAccount);

        $sequence = new InvoiceSequence(1, 'FA12345', 2026, 'Faktury', $bankAccount, null, 14, '6');
        $sequence->setSequenceId(1);
        $this->entityManager->persist($sequence);
        $this->entityManager->flush();

        $invoice = $this->createInvoice($sequence, $bankAccount, 'FA123456', '123456', '150.00', InvoicePaymentType::CASH);

        $this->tester->grabService(FioClientStub::class)
            ->setTransactions([
                $this->createFioTransactionFixture(1, 150.00, '123456'),
            ]);

        self::assertSame(0, $this->invoiceBankService->pairAllSequences([$sequence->getId()], 7));

        $pairedInvoice = $this->invoiceRepository->findOrFail($invoice->getId());
        self::assertFalse($pairedInvoice->isPaid());
        self::assertNull($pairedInvoice->getTransaction());
        self::assertNull($this->entityManager->getRepository(BankTransactionPairing::class)->findOneBy([
            'invoice' => $invoice,
            'transactionKey' => '1',
        ]));
    }

    public function testPairingSingleSequenceStillChecksDomainCollisionsAcrossOtherSequences(): void
    {
        $bankAccount = $this->createBankAccountFixture();
        $this->bankAccounts->save($bankAccount);

        $sequenceOne = $this->createSequence($bankAccount, 1, 'FA12345', false);
        $sequenceTwo = $this->createSequence($bankAccount, 2, 'FB12345', false);

        $invoiceOne = $this->createInvoice($sequenceOne, $bankAccount, 'FA123456', '123456', '150.00');
        $this->createInvoice($sequenceTwo, $bankAccount, 'FB123456', '123456', '150.00');

        $this->tester->grabService(FioClientStub::class)
            ->setTransactions([
                $this->createFioTransactionFixture(1, 150.00, '123456'),
            ]);

        self::assertSame(0, $this->invoiceBankService->pairAllSequences([$sequenceOne->getId()], 7));
        self::assertFalse($this->invoiceRepository->findOrFail($invoiceOne->getId())->isPaid());
        self::assertNull($this->entityManager->getRepository(BankTransactionPairing::class)->findOneBy([
            'invoice' => $invoiceOne,
            'transactionKey' => '1',
        ]));
    }

    public function testPairingMultipleSequencesUsesSameCollisionRules(): void
    {
        $bankAccount = $this->createBankAccountFixture();
        $this->bankAccounts->save($bankAccount);

        $sequenceOne = $this->createSequence($bankAccount, 1, 'FA12345', false);
        $sequenceTwo = $this->createSequence($bankAccount, 2, 'FB12345', false);

        $invoiceOne = $this->createInvoice($sequenceOne, $bankAccount, 'FA123456', '123456', '150.00');
        $invoiceTwo = $this->createInvoice($sequenceTwo, $bankAccount, 'FB123456', '123456', '150.00');

        $this->tester->grabService(FioClientStub::class)
            ->setTransactions([
                $this->createFioTransactionFixture(1, 150.00, '123456'),
            ]);

        self::assertSame(0, $this->invoiceBankService->pairAllSequences([$sequenceOne->getId(), $sequenceTwo->getId()], 7));
        self::assertFalse($this->invoiceRepository->findOrFail($invoiceOne->getId())->isPaid());
        self::assertFalse($this->invoiceRepository->findOrFail($invoiceTwo->getId())->isPaid());
        self::assertSame([], $this->entityManager->getRepository(BankTransactionPairing::class)->findBy([]));
    }

    public function testPairAutomaticSequencesUsesStoredTransactionsAndRespectsEnabledFlag(): void
    {
        $bankAccount = $this->createBankAccountFixture();
        $this->bankAccounts->save($bankAccount);

        $enabledSequence = $this->createSequence($bankAccount, 1, 'FA12345', true);
        $disabledSequence = $this->createSequence($bankAccount, 2, 'FA54321', false);

        $enabledInvoice = $this->createInvoice($enabledSequence, $bankAccount, 'FA123456', '123456', '150.00');
        $disabledInvoice = $this->createInvoice($disabledSequence, $bankAccount, 'FA543217', '543217', '200.00');

        $this->tester->grabService(FioClientStub::class)
            ->setTransactions([
                $this->createFioTransactionFixture(1, 150.00, '123456'),
                $this->createFioTransactionFixture(2, 200.00, '543217'),
            ]);

        self::assertSame(0, $this->invoiceBankService->pairAutomaticSequences());
        self::assertFalse($this->invoiceRepository->findOrFail($enabledInvoice->getId())->isPaid());
        self::assertFalse($this->invoiceRepository->findOrFail($disabledInvoice->getId())->isPaid());

        $this->entityManager->persist(new BankTransaction(
            $bankAccount,
            new Transaction(
                'stored-enabled',
                BankTransactionSource::FIO,
                new DateTimeImmutable(),
                150.00,
                '',
                'Payer',
                123456,
                null,
                'Poznámka',
                '1001',
            ),
            new DateTimeImmutable(),
        ));
        $this->entityManager->persist(new BankTransaction(
            $bankAccount,
            new Transaction(
                'stored-disabled',
                BankTransactionSource::FIO,
                new DateTimeImmutable(),
                200.00,
                '',
                'Payer',
                543217,
                null,
                'Poznámka',
                '1002',
            ),
            new DateTimeImmutable(),
        ));
        $this->entityManager->flush();

        self::assertSame(1, $this->invoiceBankService->pairAutomaticSequences());
        self::assertTrue($this->invoiceRepository->findOrFail($enabledInvoice->getId())->isPaid());
        self::assertFalse($this->invoiceRepository->findOrFail($disabledInvoice->getId())->isPaid());
        self::assertNotNull($this->invoiceRepository->findOrFail($enabledInvoice->getId())->getSequence()->getLastPairing());
        self::assertNull($this->invoiceRepository->findOrFail($disabledInvoice->getId())->getSequence()->getLastPairing());
    }

    private function createSequence(BankAccount $bankAccount, int $sequenceId, string $sequencePrefix, bool $automaticPairingEnabled): InvoiceSequence
    {
        $sequence = $this->createInvoiceSequenceFixture($bankAccount, $sequenceId, $sequencePrefix, $automaticPairingEnabled, 30);
        $this->entityManager->persist($sequence);
        $this->entityManager->flush();

        return $sequence;
    }

    private function createInvoice(
        InvoiceSequence $sequence,
        BankAccount $bankAccount,
        string $number,
        string $variableSymbol,
        string $amount,
        InvoicePaymentType $paymentType = InvoicePaymentType::TRANSFER,
    ): Invoice {
        $invoice = $this->createInvoiceFixture($sequence, $bankAccount, $number, $variableSymbol, $amount, $paymentType);
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        return $invoice;
    }
}
