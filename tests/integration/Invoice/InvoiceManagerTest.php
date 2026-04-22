<?php

declare(strict_types=1);

namespace Invoice;

use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Invoice\Embeddable\InvoiceCustomer;
use App\Model\Invoice\Embeddable\InvoiceSupplier;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Invoice\Manager\InvoiceManager;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Invoice\Repository\InvoiceUnitSettingRepository;
use App\Model\Logger\LoggerService;
use App\Model\Payment\Repositories\IPaymentRepository;
use App\Model\Payment\Services\VariableSymbolCollisionChecker;
use App\Model\Unit\UnitService;
use App\Model\User\UserService;
use DateTimeImmutable;
use IntegrationTest;
use stdClass;

final class InvoiceManagerTest extends IntegrationTest
{
    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [
            Invoice::class,
            InvoiceSequence::class,
        ];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);

        parent::_before();
    }

    public function testCreateAssignsInvoiceNumberAndVariableSymbolFromSequence(): void
    {
        $sequence = new InvoiceSequence(123, 'FAO93', 2026, 'Hlavní řada', null, null, 14, '00001');
        $sequence->setSequenceId(1);
        $this->entityManager->persist($sequence);
        $this->entityManager->flush();

        $manager = new InvoiceManager(
            $this->entityManager,
            $this->createMock(UserService::class),
            $this->createMock(LoggerService::class),
            new InvoiceRepository($this->entityManager),
            $this->createVariableSymbolCollisionChecker(),
            $this->createMock(UnitService::class),
            $this->createMock(InvoiceUnitSettingRepository::class),
        );

        $firstInvoice = $this->createInvoice($sequence);
        $manager->create($firstInvoice);

        self::assertSame(1, $firstInvoice->getInvoiceId());
        self::assertSame('FAO9300001', $firstInvoice->getInvoiceNumber());
        self::assertSame('9300001', (string) $firstInvoice->getVariableSymbol());

        $secondInvoice = $this->createInvoice($sequence);
        $manager->create($secondInvoice);

        self::assertSame(2, $secondInvoice->getInvoiceId());
        self::assertSame('FAO9300002', $secondInvoice->getInvoiceNumber());
        self::assertSame('9300002', (string) $secondInvoice->getVariableSymbol());
    }

    public function testCreateCashInvoiceWithoutBankAccount(): void
    {
        $sequence = new InvoiceSequence(123, 'HOT', 2026, 'Hotovostní řada', null, null, 14, '00001');
        $sequence->setSequenceId(1);
        $this->entityManager->persist($sequence);
        $this->entityManager->flush();

        $manager = new InvoiceManager(
            $this->entityManager,
            $this->createMock(UserService::class),
            $this->createMock(LoggerService::class),
            new InvoiceRepository($this->entityManager),
            $this->createVariableSymbolCollisionChecker(),
            $this->createMock(UnitService::class),
            $this->createMock(InvoiceUnitSettingRepository::class),
        );

        $invoice = new Invoice(
            $sequence,
            new InvoiceSupplier(123, 'Středisko Test', 'Křižíkova 12', 'Praha', '18600', '12345678'),
            new InvoiceCustomer('Jan Novák', 'Ulice', 'Brno', '60200', '1', '', ''),
            'Tester',
            new DateTimeImmutable('2026-03-20'),
            new DateTimeImmutable('2026-03-01'),
            new DateTimeImmutable('2026-03-01'),
            InvoicePaymentType::CASH,
            null,
        );

        $manager->create($invoice);

        self::assertSame(1, $invoice->getInvoiceId());
        self::assertSame('HOT00001', $invoice->getInvoiceNumber());
        self::assertSame('1', (string) $invoice->getVariableSymbol());
        self::assertNull($invoice->getAccountNumber());
        self::assertSame('issued', $invoice->getState());
    }

    public function testMarkAsDeliveredPersistsSentState(): void
    {
        $sequence = new InvoiceSequence(123, 'HOT', 2026, 'Hotovostní řada', null, null, 14, '00001');
        $sequence->setSequenceId(1);
        $this->entityManager->persist($sequence);
        $this->entityManager->flush();

        $invoice = new Invoice(
            $sequence,
            new InvoiceSupplier(123, 'Středisko Test', 'Křižíkova 12', 'Praha', '18600', '12345678'),
            new InvoiceCustomer('Jan Novák', 'Ulice', 'Brno', '60200', '1', '', ''),
            'Tester',
            new DateTimeImmutable('2026-03-20'),
            new DateTimeImmutable('2026-03-01'),
            new DateTimeImmutable('2026-03-01'),
            InvoicePaymentType::CASH,
            null,
        );

        $manager = $this->createManager('Předávající Uživatel');
        $manager->create($invoice);

        self::assertTrue($manager->markAsDelivered($invoice));
        self::assertTrue($invoice->hasBeenDelivered());
        self::assertSame('Předávající Uživatel', $invoice->getSentBy());

        $this->entityManager->refresh($invoice);

        self::assertTrue($invoice->hasBeenDelivered());
        self::assertSame('Předávající Uživatel', $invoice->getSentBy());
    }

    public function testMarkAsPaidInCashPersistsReceiptNumber(): void
    {
        $sequence = new InvoiceSequence(123, 'HOT', 2026, 'Hotovostní řada', null, null, 14, '00001');
        $sequence->setSequenceId(1);
        $this->entityManager->persist($sequence);
        $this->entityManager->flush();

        $invoice = new Invoice(
            $sequence,
            new InvoiceSupplier(123, 'Středisko Test', 'Křižíkova 12', 'Praha', '18600', '12345678'),
            new InvoiceCustomer('Jan Novák', 'Ulice', 'Brno', '60200', '1', '', ''),
            'Tester',
            new DateTimeImmutable('2026-03-20'),
            new DateTimeImmutable('2026-03-01'),
            new DateTimeImmutable('2026-03-01'),
            InvoicePaymentType::CASH,
            null,
        );

        $manager = $this->createManager('Pokladník');
        $manager->create($invoice);

        self::assertTrue($manager->markAsPaidInCash($invoice, 'PD-123'));

        $this->entityManager->refresh($invoice);

        self::assertTrue($invoice->isPaid());
        self::assertSame('PD-123', $invoice->getCashReceiptNumber());
        self::assertSame('Pokladník', $invoice->getClosedByUsername());
        self::assertNotNull($invoice->getClosedAt());
    }

    public function testCreatedInvoiceRemainsIssuedAfterRefresh(): void
    {
        $sequence = new InvoiceSequence(123, 'BEZ', 2026, 'Bankovní řada', null, null, 14, '00001');
        $sequence->setSequenceId(1);
        $this->entityManager->persist($sequence);
        $this->entityManager->flush();

        $manager = $this->createManager();
        $invoice = $this->createInvoice($sequence);
        $manager->create($invoice);

        $this->entityManager->refresh($invoice);

        self::assertFalse($invoice->isPaid());
        self::assertSame('issued', $invoice->getState());
    }

    private function createManager(string $displayName = 'Tester'): InvoiceManager
    {
        $userService = $this->createMock(UserService::class);
        $userDetail = new stdClass();
        $userDetail->DisplayName = $displayName;
        $userService->method('getUserDetail')->willReturn($userDetail);

        return new InvoiceManager(
            $this->entityManager,
            $userService,
            $this->createMock(LoggerService::class),
            new InvoiceRepository($this->entityManager),
            $this->createVariableSymbolCollisionChecker(),
            $this->createMock(UnitService::class),
            $this->createMock(InvoiceUnitSettingRepository::class),
        );
    }

    private function createVariableSymbolCollisionChecker(): VariableSymbolCollisionChecker
    {
        $payments = $this->createMock(IPaymentRepository::class);
        $payments->method('existsOpenPaymentWithVariableSymbolForBankAccount')->willReturn(false);

        return new VariableSymbolCollisionChecker($payments, new InvoiceRepository($this->entityManager));
    }

    private function createInvoice(InvoiceSequence $sequence): Invoice
    {
        return new Invoice(
            $sequence,
            new InvoiceSupplier(123, 'Středisko Test', 'Křižíkova 12', 'Praha', '18600', '12345678'),
            new InvoiceCustomer('Jan Novák', 'Ulice', 'Brno', '60200', '1', '', ''),
            'Tester',
            new DateTimeImmutable('2026-03-20'),
            new DateTimeImmutable('2026-03-01'),
            new DateTimeImmutable('2026-03-01'),
            InvoicePaymentType::TRANSFER,
            AccountNumber::fromString('2300228890/2010'),
        );
    }
}
