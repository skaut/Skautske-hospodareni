<?php

declare(strict_types=1);

namespace Repository;

use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Invoice\Embeddable\InvoiceCustomer;
use App\Model\Invoice\Embeddable\InvoiceSupplier;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Payment\VariableSymbol;
use DateTimeImmutable;
use IntegrationTest;

final class InvoiceRepositoryTest extends IntegrationTest
{
    private InvoiceRepository $repository;

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

        $this->repository = new InvoiceRepository($this->entityManager);
    }

    public function testInvoiceNumberExistsOnlyWithinSingleSequence(): void
    {
        $firstSequence = $this->createSequence(1, 'INV', 1);
        $secondSequence = $this->createSequence(1, 'ALT', 2);

        $this->entityManager->persist($firstSequence);
        $this->entityManager->persist($secondSequence);
        $this->entityManager->persist($this->createInvoice($firstSequence, '2026-001', '123456'));
        $this->entityManager->persist($this->createInvoice($secondSequence, '2026-001', '123457'));
        $this->entityManager->flush();

        self::assertTrue($this->repository->invoiceNumberExists($firstSequence, '2026-001'));
        self::assertTrue($this->repository->invoiceNumberExists($secondSequence, '2026-001'));
        self::assertFalse($this->repository->invoiceNumberExists($firstSequence, '2026-999'));
    }

    public function testNextInvoiceIdStartsFromConfiguredFirstNumber(): void
    {
        $sequence = $this->createSequence(1, 'INV', 1, '00042');
        $this->entityManager->persist($sequence);
        $this->entityManager->flush();

        self::assertSame(42, $this->repository->getNextInvoiceId($sequence));
    }

    public function testNextInvoiceIdContinuesFromHighestAssignedInvoiceId(): void
    {
        $sequence = $this->createSequence(1, 'INV', 1);
        $invoice = $this->createInvoice($sequence, 'INV00007', '7');
        $invoice->setInvoiceId(7);

        $this->entityManager->persist($sequence);
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        self::assertSame(8, $this->repository->getNextInvoiceId($sequence));
    }

    public function testNextInvoiceIdUsesStoredGeneratedInvoiceNumberWhenInvoiceIdIsMissing(): void
    {
        $sequence = $this->createSequence(1, 'FAO93', 1);
        $invoice = $this->createInvoice($sequence, 'FAO9300042', '9300042');

        $this->entityManager->persist($sequence);
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        self::assertSame(43, $this->repository->getNextInvoiceId($sequence));
    }

    private function createSequence(int $unitId, string $sequence, int $sequenceId, string $firstNumber = '00001'): InvoiceSequence
    {
        $invoiceSequence = new InvoiceSequence($unitId, $sequence, 2026, 'Testovací řada', null, null, 14, $firstNumber);
        $invoiceSequence->setSequenceId($sequenceId);

        return $invoiceSequence;
    }

    private function createInvoice(InvoiceSequence $sequence, string $invoiceNumber, string $variableSymbol): Invoice
    {
        return new Invoice(
            $sequence,
            new InvoiceSupplier($sequence->getUnit(), 'Středisko Test', 'Křižíkova 12', 'Praha', '18600', '12345678', '+420123456789'),
            new InvoiceCustomer('Odběratel', 'Ulice', 'Brno', '60200', '1', '2', '87654321'),
            'Tester',
            new DateTimeImmutable('2026-03-20'),
            new DateTimeImmutable('2026-03-01'),
            new DateTimeImmutable('2026-03-01'),
            InvoicePaymentType::TRANSFER,
            AccountNumber::fromString('2300228890/2010'),
            $invoiceNumber,
            new VariableSymbol($variableSymbol),
        );
    }
}
