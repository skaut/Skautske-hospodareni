<?php

declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\EntityManagerInterface;
use Entity\Invoice;

class InvoiceRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return Invoice::class;
    }

    public function getGrid()
    {
        /** @var Invoice[] $invoice */
        $invoice = $this->findAll();

        return $invoice;
    }
}
