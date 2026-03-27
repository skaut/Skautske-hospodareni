<?php

declare(strict_types=1);

namespace App\Model\Invoice\Manager;

use App\Model\Infrastructure\Manager\AbstractManager;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Logger\LoggerService;
use App\Model\Payment\Services\VariableSymbolCollisionChecker;
use App\Model\User\UserService;
use DateTimeImmutable;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class InvoiceManager extends AbstractManager
{
    public function __construct(
        EntityManagerInterface $entityManager,
        protected UserService $userService,
        protected LoggerService $logger,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly VariableSymbolCollisionChecker $variableSymbolCollisionChecker,
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
}
