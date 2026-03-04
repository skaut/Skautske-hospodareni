<?php

declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Entity\Invoice;
use Entity\InvoiceSequence;

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

    /**
     * @return Invoice[]
     */
    public function getGrid(InvoiceSequence $invoiceSequence): array
    {
        /** @var Invoice[] $invoice */
        $invoice = $this->findBy(['sequence' => $invoiceSequence], ['invoiceId' => 'ASC']);

        return $invoice;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getNextInvoiceId(InvoiceSequence $invoiceSequence): int
    {
        try {
            $invoiceId = $this->createQueryBuilder('entity')
                ->select('MAX(entity.invoiceId) as invoiceId')
                ->where('entity.sequence = :sequence')
                ->setParameter('sequence', $invoiceSequence)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException) {
            return 1;
        }

        return $invoiceId === null ? 1 : (int) $invoiceId + 1;
    }
}
