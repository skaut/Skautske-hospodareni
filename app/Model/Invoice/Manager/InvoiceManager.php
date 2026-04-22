<?php

declare(strict_types=1);

namespace App\Model\Invoice\Manager;

use App\Model\Infrastructure\Manager\AbstractManager;
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
use App\Model\Payment\Services\VariableSymbolCollisionChecker;
use App\Model\Unit\UnitService;
use App\Model\User\UserService;
use DateTimeImmutable;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class InvoiceManager extends AbstractManager
{
    public function __construct(
        EntityManagerInterface $entityManager,
        protected UserService $userService,
        protected LoggerService $logger,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly VariableSymbolCollisionChecker $variableSymbolCollisionChecker,
        private readonly UnitService $unitService,
        private readonly InvoiceUnitSettingRepository $invoiceUnitSettingRepository,
    ) {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return Invoice::class;
    }

    public function create(Invoice $invoice): Invoice
    {
        $this->wrapInTransaction(function () use ($invoice): void {
            $sequence = $invoice->getSequence();
            $this->lock($sequence, LockMode::PESSIMISTIC_WRITE);

            $invoiceId = $this->invoiceRepository->getNextInvoiceId($sequence);
            $invoice->assignNumbering(
                $invoiceId,
                $sequence->formatInvoiceNumber($invoiceId),
                $sequence->generateVariableSymbol($invoiceId),
            );
            $this->variableSymbolCollisionChecker->assertUniqueForInvoice($invoice, $invoice->getVariableSymbol());

            $this->em->persist($invoice);
            $this->saveEntity($invoice);
        });

        return $invoice;
    }

    public function delete(Invoice $invoice): void
    {
        $this->wrapInTransaction(function () use ($invoice): void {
            // $this->logger->log($invoice->getUnit(), $this->userService->getUserDetail()->ID, 'Invoice paymet deleted', Type::get(Type::INVOICE_SEQUENCE), $invoiceSequence->getId());
            $this->deleteEntity($invoice);
        });
    }

    public function update(Invoice $invoice): Invoice
    {
        $this->em->persist($invoice);
        $this->saveEntity($invoice);

        return $invoice;
    }

    public function duplicateToSequence(Invoice $sourceInvoice, InvoiceSequence $targetSequence): Invoice
    {
        if ($sourceInvoice->getSequence()->getId() === $targetSequence->getId()) {
            throw new InvalidArgumentException('Vyberte jinou fakturační řadu.');
        }

        if (! $targetSequence->isOpen()) {
            throw new InvalidArgumentException('Fakturu lze duplikovat pouze do otevřené fakturační řady.');
        }

        if (
            $sourceInvoice->getPaymentType()->value === InvoicePaymentType::TRANSFER->value
            && $targetSequence->getBankAccount() === null
        ) {
            throw new InvalidArgumentException('Do zvolené řady nelze duplikovat fakturu hrazenou převodem bez bankovního účtu.');
        }

        $dateOfIssue = new DateTimeImmutable('today');
        $dueDate = $dateOfIssue->modify(sprintf('+%d days', $targetSequence->getDefaultDueDate() ?? 0));
        $bankAccountNumber = $targetSequence->getBankAccount()?->getNumber();

        $invoice = new Invoice(
            $targetSequence,
            $this->createInvoiceSupplier($targetSequence),
            $this->copyCustomer($sourceInvoice),
            $sourceInvoice->getIssuedBy(),
            $dueDate,
            $dateOfIssue,
            $dateOfIssue,
            $sourceInvoice->getPaymentType(),
            $bankAccountNumber,
            null,
            null,
            $bankAccountNumber?->getBankName(),
            $bankAccountNumber?->getIban(),
            $bankAccountNumber?->getBic(),
        );

        foreach ($sourceInvoice->getItems() as $item) {
            $invoice->addItem($this->copyItem($item));
        }

        return $this->create($invoice);
    }

    public function markAsDelivered(Invoice $invoice): bool
    {
        return $this->wrapInTransaction(function () use ($invoice): bool {
            $user = $this->userService->getUserDetail();
            $userName = $user->DisplayName ?? $invoice->getIssuedBy();
            $wasMarked = $invoice->markAsDelivered(new DateTimeImmutable(), (string) $userName);

            if ($wasMarked) {
                $this->saveEntity($invoice);
            }

            return $wasMarked;
        });
    }

    public function markAsPaidInCash(Invoice $invoice, string $receiptNumber): bool
    {
        return $this->wrapInTransaction(function () use ($invoice, $receiptNumber): bool {
            $user = $this->userService->getUserDetail();
            $userName = $user->DisplayName ?? $invoice->getIssuedBy();
            $wasMarked = $invoice->markAsPaidInCash($receiptNumber, new DateTimeImmutable(), (string) $userName);

            if ($wasMarked) {
                $this->saveEntity($invoice);
            }

            return $wasMarked;
        });
    }

    private function createInvoiceSupplier(InvoiceSequence $invoiceSequence): InvoiceSupplier
    {
        $year = $invoiceSequence->getYear();
        if ($year !== null) {
            $setting = $this->invoiceUnitSettingRepository->findByUnitAndYear($invoiceSequence->getUnit(), $year);
            if ($setting instanceof InvoiceUnitSetting) {
                return $setting->toInvoiceSupplier();
            }
        }

        return InvoiceSupplier::fromOfficialUnit(
            $this->unitService->getOfficialUnit($invoiceSequence->getUnit()),
            $invoiceSequence->getPhone(),
        );
    }

    private function copyCustomer(Invoice $sourceInvoice): InvoiceCustomer
    {
        $customer = $sourceInvoice->getCustomer();
        $address = $customer->getAddress();

        return new InvoiceCustomer(
            $customer->getName(),
            $address->getStreet(),
            $address->getCity(),
            $address->getZipCode(),
            (string) $address->getStreetNumber(),
            (string) $address->getStreetNumberSuffix(),
            $customer->getCompanyNumber(),
            $customer->getVatNumber(),
            $customer->isVatPayer(),
        );
    }

    private function copyItem(InvoiceItem $item): InvoiceItem
    {
        return new InvoiceItem(
            $item->getPrice(),
            $item->getPurpose(),
            $item->getQuantity(),
            $item->getUnit(),
        );
    }
}
