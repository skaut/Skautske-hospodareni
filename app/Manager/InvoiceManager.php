<?php

declare(strict_types=1);

namespace Manager;

use Doctrine\ORM\EntityManagerInterface;
use Entity\Invoice;
use Model\LoggerService;
use Model\UserService;

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

    public function create(Invoice $invoice): Invoice
    {
        $this->em->persist($invoice);
        $this->saveEntity($invoice);

        return $invoice;
    }

    public function delete(Invoice $invoice): void
    {
        $this->wrapInTransaction(function () use ($invoice): void {
            //$this->logger->log($invoice->getUnit(), $this->userService->getUserDetail()->ID, 'Invoice paymet deleted', Type::get(Type::INVOICE_SEQUENCE), $invoiceSequence->getId());
            $this->deleteEntity($invoice);
        });
    }
}
