<?php

declare(strict_types=1);

namespace Manager;

use Doctrine\ORM\EntityManagerInterface;
use Entity\Invoice;
use LogicException;
use Model\LoggerService;
use Model\Payment\InvalidVariableSymbol;
use Model\Payment\VariableSymbol;
use Model\UserService;
use Repository\InvoiceRepository;

class InvoiceManager extends AbstractManager
{
    public function __construct(EntityManagerInterface $entityManager, protected UserService $userService, protected LoggerService $logger)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return Invoice::class;
    }

    public function create(Invoice $invoice, InvoiceRepository $invoiceRepository): Invoice
    {
        $this->wrapInTransaction(function () use ($invoice, $invoiceRepository): void {
            $invoice->setInvoiceId($invoiceRepository->getNextInvoiceId($invoice->getSequence()));
            try {
                $variableSymbol = new VariableSymbol(sprintf('%d%04d%d', $invoice->getSequence()->getSequenceId(), $invoice->getInvoiceId(), $invoice->getSequence()->getYear()));
            } catch (InvalidVariableSymbol $e) {
                throw new LogicException('Generated invoice variable symbol is invalid.', 0, $e);
            }
            $invoice->setVariableSymbol($variableSymbol);

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
}
