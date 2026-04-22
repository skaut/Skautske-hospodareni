<?php

declare(strict_types=1);

namespace App\Model\Invoice\Manager;

use App\Model\Invoice\Embeddable\InvoiceCustomer;
use App\Model\Invoice\Embeddable\InvoiceSupplier;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceItem;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Entity\InvoiceUnitSetting;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Invoice\Repository\InvoiceUnitSettingRepository;
use App\Model\Logger\LoggerService;
use App\Model\Payment\Repositories\IPaymentRepository;
use App\Model\Payment\Services\VariableSymbolCollisionChecker;
use App\Model\Unit\UnitService;
use App\Model\User\UserService;
use Brick\Math\BigDecimal;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class InvoiceManagerTest extends TestCase
{
    public function testDuplicateToSequenceCopiesRequestedDataAndUsesTargetRules(): void
    {
        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('wrapInTransaction')
            ->willReturnCallback(static fn (callable $callback): mixed => $callback());
        $entityManager->expects(self::once())
            ->method('lock');
        $entityManager->expects(self::exactly(2))
            ->method('persist');
        $entityManager->expects(self::once())
            ->method('flush');

        /** @var InvoiceRepository&MockObject $invoiceRepository */
        $invoiceRepository = $this->createMock(InvoiceRepository::class);
        $invoiceRepository->expects(self::once())
            ->method('getNextInvoiceId')
            ->willReturn(7);

        /** @var IPaymentRepository&MockObject $paymentRepository */
        $paymentRepository = $this->createMock(IPaymentRepository::class);
        $paymentRepository->expects(self::once())
            ->method('existsOpenPaymentWithVariableSymbolForBankAccount')
            ->willReturn(false);

        $invoiceRepository->expects(self::once())
            ->method('existsOpenTransferInvoiceWithVariableSymbolForBankAccount')
            ->willReturn(false);

        $collisionChecker = new VariableSymbolCollisionChecker($paymentRepository, $invoiceRepository);

        /** @var UnitService&MockObject $unitService */
        $unitService = $this->createMock(UnitService::class);
        $unitService->expects(self::never())
            ->method('getOfficialUnit');

        /** @var InvoiceUnitSettingRepository&MockObject $invoiceUnitSettingRepository */
        $invoiceUnitSettingRepository = $this->createMock(InvoiceUnitSettingRepository::class);
        $invoiceUnitSettingRepository->expects(self::once())
            ->method('findByUnitAndYear')
            ->with(456, 2029)
            ->willReturn(new InvoiceUnitSetting(
                456,
                2029,
                'Cílové středisko',
                'Cílová 1',
                'Praha',
                '11000',
                '87654321',
                '+420111222333',
            ));

        /** @var UserService&MockObject $userService */
        $userService = $this->createMock(UserService::class);
        /** @var LoggerService&MockObject $loggerService */
        $loggerService = $this->createMock(LoggerService::class);

        $manager = new InvoiceManager(
            $entityManager,
            $userService,
            $loggerService,
            $invoiceRepository,
            $collisionChecker,
            $unitService,
            $invoiceUnitSettingRepository,
        );

        $sourceSequence = new InvoiceSequence(123, 'SRC29', 2029, 'Zdrojová řada', null, null, 14);
        $targetSequence = new InvoiceSequence(456, 'TGT29', 2029, 'Cílová řada', null, null, 10);
        $this->setEntityId($sourceSequence, 101);
        $this->setEntityId($targetSequence, 202);

        $sourceInvoice = new Invoice(
            $sourceSequence,
            new InvoiceSupplier(123, 'Zdrojové středisko', 'Zdrojová 1', 'Brno', '60200', '12345678'),
            new InvoiceCustomer('Jan Novák', 'Masarykova', 'Brno', '60200', '12', '3', '12345678', 'CZ12345678', true),
            'Původní vystavil',
            new DateTimeImmutable('2029-03-15'),
            new DateTimeImmutable('2029-03-01'),
            new DateTimeImmutable('2029-03-01'),
            InvoicePaymentType::CASH,
            null,
        );
        $sourceInvoice->addItem(new InvoiceItem(BigDecimal::of('1500.00'), 'První položka', 2, 'ks'));
        $sourceInvoice->addItem(new InvoiceItem(BigDecimal::of('99.00'), 'Druhá položka', 1, 'hod'));

        $duplicatedInvoice = $manager->duplicateToSequence($sourceInvoice, $targetSequence);

        self::assertSame($targetSequence, $duplicatedInvoice->getSequence());
        self::assertSame('Původní vystavil', $duplicatedInvoice->getIssuedBy());
        self::assertSame(InvoicePaymentType::CASH, $duplicatedInvoice->getPaymentType());
        self::assertSame('TGT2900007', $duplicatedInvoice->getInvoiceNumber());
        self::assertSame('2900007', (string) $duplicatedInvoice->getVariableSymbol());
        self::assertSame('Jan Novák', $duplicatedInvoice->getCustomer()->getName());
        self::assertSame('12345678', $duplicatedInvoice->getCustomer()->getCompanyNumber());
        self::assertSame('CZ12345678', $duplicatedInvoice->getCustomer()->getVatNumber());
        self::assertSame('Cílové středisko', $duplicatedInvoice->getSupplier()->getName());
        self::assertCount(2, $duplicatedInvoice->getItems());

        $today = new DateTimeImmutable('today');
        self::assertEquals($today, $duplicatedInvoice->getDateOfIssue());
        self::assertEquals($today, $duplicatedInvoice->getDateOfTaxPayment());
        self::assertEquals($today->add(new DateInterval('P10D')), $duplicatedInvoice->getDueDate());

        $items = $duplicatedInvoice->getItems()->toArray();
        self::assertSame('První položka', $items[0]->getPurpose());
        self::assertSame(2, $items[0]->getQuantity());
        self::assertSame('1500.00', (string) $items[0]->getPrice());
        self::assertSame('Druhá položka', $items[1]->getPurpose());
        self::assertSame('hod', $items[1]->getUnit());
    }

    private function setEntityId(object $entity, int $id): void
    {
        $property = new ReflectionProperty($entity, 'id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}
