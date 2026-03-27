<?php

declare(strict_types=1);

namespace Repository;

use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Invoice\Embeddable\InvoiceCustomer;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Entity\InvoiceUnitSetting;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Invoice\Repository\InvoiceUnitSettingRepository;
use App\Model\Payment\VariableSymbol;
use DateTimeImmutable;
use IntegrationTest;

final class InvoiceYearlySettingsSnapshotTest extends IntegrationTest
{
    private InvoiceUnitSettingRepository $settings;

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [
            Invoice::class,
            InvoiceSequence::class,
            InvoiceUnitSetting::class,
        ];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);

        parent::_before();

        $this->settings = new InvoiceUnitSettingRepository($this->entityManager);
    }

    public function testUnitSettingsAreSeparatedByYear(): void
    {
        $setting2026 = new InvoiceUnitSetting(
            123,
            2026,
            'Středisko Test',
            'Křižíkova 12',
            'Praha',
            '18600',
            '12345678',
        );
        $setting2027 = new InvoiceUnitSetting(
            123,
            2027,
            'Středisko Test',
            'Křižíkova 12',
            'Praha',
            '18600',
            '12345678',
            '+420123456789',
        );

        $this->entityManager->persist($setting2026);
        $this->entityManager->persist($setting2027);
        $this->entityManager->flush();

        $loaded2026 = $this->settings->findByUnitAndYear(123, 2026);
        $loaded2027 = $this->settings->findByUnitAndYear(123, 2027);

        self::assertNotNull($loaded2026);
        self::assertNotNull($loaded2027);
        self::assertNull($loaded2026->getPhone());
        self::assertSame('+420123456789', $loaded2027->getPhone());
    }

    public function testHistoricalInvoiceKeepsSupplierSnapshotWhenUnitSettingsChangeInNewYear(): void
    {
        $setting2026 = new InvoiceUnitSetting(
            123,
            2026,
            'Středisko Test',
            'Křižíkova 12',
            'Praha',
            '18600',
            '12345678',
        );
        $setting2027 = new InvoiceUnitSetting(
            123,
            2027,
            'Středisko Test',
            'Křižíkova 12',
            'Praha',
            '18600',
            '12345678',
            '+420123456789',
        );

        $sequence2026 = new InvoiceSequence(123, 'INV', 2026, 'Řada 2026');
        $sequence2026->setSequenceId(1);
        $sequence2027 = new InvoiceSequence(123, 'INV', 2027, 'Řada 2027');
        $sequence2027->setSequenceId(1);

        $invoice2026 = $this->createInvoice(
            $sequence2026,
            $setting2026,
            '2026-001',
            '123456',
        );
        $invoice2027 = $this->createInvoice(
            $sequence2027,
            $setting2027,
            '2027-001',
            '223456',
        );

        $this->entityManager->persist($setting2026);
        $this->entityManager->persist($setting2027);
        $this->entityManager->persist($invoice2026->getSequence());
        $this->entityManager->persist($invoice2027->getSequence());
        $this->entityManager->persist($invoice2026);
        $this->entityManager->persist($invoice2027);
        $this->entityManager->flush();
        $this->entityManager->refresh($invoice2026);
        $this->entityManager->refresh($invoice2027);

        self::assertNull($invoice2026->getSupplier()->getPhone());
        self::assertSame('2026-001', $invoice2026->getInvoiceNumber());

        self::assertSame('+420123456789', $invoice2027->getSupplier()->getPhone());
        self::assertSame('2027-001', $invoice2027->getInvoiceNumber());
    }

    private function createInvoice(InvoiceSequence $sequence, InvoiceUnitSetting $setting, string $invoiceNumber, string $variableSymbol): Invoice
    {
        return new Invoice(
            $sequence,
            $setting->toInvoiceSupplier(),
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
