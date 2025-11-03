<?php

declare(strict_types=1);

namespace Manager;

use Doctrine\ORM\EntityManagerInterface;
use Entity\InvoiceSequence;
use Model\Logger\Log\Type;
use Model\LoggerService;
use Model\UserService;

class InvoiceSequenceManager extends AbstractManager
{
    public function __construct(EntityManagerInterface $entityManager, protected UserService $userService, protected LoggerService $logger)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return InvoiceSequence::class;
    }

    public function create(InvoiceSequence $invoiceSequence): InvoiceSequence
    {
        $this->em->persist($invoiceSequence);
        $this->saveEntity($invoiceSequence);

        return $invoiceSequence;
    }

    public function delete(InvoiceSequence $invoiceSequence): void
    {
        $this->wrapInTransaction(function () use ($invoiceSequence): void {
            $this->logger->log($invoiceSequence->getUnit(), $this->userService->getUserDetail()->ID, 'Invoice paymet deleted', Type::get(Type::INVOICE_SEQUENCE), $invoiceSequence->getId());
            $this->deleteEntity($invoiceSequence);
        });
    }
}
