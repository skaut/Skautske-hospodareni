<?php

declare(strict_types=1);

namespace App\Model\Invoice\Repository;

use App\Model\Infrastructure\Repository\AbstractRepository;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Invoice\Enum\InvoiceState;
use App\Model\Payment\VariableSymbol;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use LogicException;

use function count;
use function preg_match;

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
        $invoice = $this->findBy(['sequence' => $invoiceSequence], ['dateOfIssue' => 'DESC', 'id' => 'DESC']);

        return $invoice;
    }

    /**
     * @param  int[]     $unitIds
     * @return Invoice[]
     */
    public function getGridByUnits(array $unitIds): array
    {
        if ($unitIds === []) {
            return [];
        }

        /** @var Invoice[] $invoice */
        $invoice = $this->createQueryBuilder('entity')
            ->innerJoin('entity.sequence', 'sequence')
            ->where('sequence.unit IN (:unitIds)')
            ->setParameter('unitIds', $unitIds)
            ->orderBy('entity.dateOfIssue', 'DESC')
            ->addOrderBy('entity.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $invoice;
    }

    /**
     * @param int[] $unitIds
     */
    public function findAccessibleByUnits(int $id, array $unitIds): ?Invoice
    {
        return $this->createQueryBuilder('entity')
            ->innerJoin('entity.sequence', 'sequence')
            ->where('entity.id = :id')
            ->andWhere('sequence.unit IN (:unitIds)')
            ->setParameter('id', $id)
            ->setParameter('unitIds', $unitIds)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function invoiceNumberExists(InvoiceSequence $invoiceSequence, string $invoiceNumber): bool
    {
        try {
            $matches = $this->createQueryBuilder('entity')
                ->select('COUNT(entity.id)')
                ->where('entity.sequence = :sequence')
                ->andWhere('entity.invoiceNumber = :invoiceNumber')
                ->setParameter('sequence', $invoiceSequence)
                ->setParameter('invoiceNumber', $invoiceNumber)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException) {
            return false;
        }

        return (int) $matches > 0;
    }

    public function hasInvoicesInSequence(InvoiceSequence $invoiceSequence): bool
    {
        try {
            $matches = $this->createQueryBuilder('entity')
                ->select('COUNT(entity.id)')
                ->where('entity.sequence = :sequence')
                ->setParameter('sequence', $invoiceSequence)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException) {
            return false;
        }

        return (int) $matches > 0;
    }

    public function countInvoicesInSequence(InvoiceSequence $invoiceSequence): int
    {
        try {
            $matches = $this->createQueryBuilder('entity')
                ->select('COUNT(entity.id)')
                ->where('entity.sequence = :sequence')
                ->setParameter('sequence', $invoiceSequence)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException) {
            return 0;
        }

        return (int) $matches;
    }

    public function getNextInvoiceId(InvoiceSequence $invoiceSequence): int
    {
        /** @var Invoice[] $invoices */
        $invoices = $this->findBy(['sequence' => $invoiceSequence]);
        $highestNumber = null;

        foreach ($invoices as $invoice) {
            $invoiceId = null;

            try {
                $invoiceId = $invoice->getInvoiceId();
            } catch (LogicException) {
            }

            if ($invoiceId !== null) {
                $highestNumber = $highestNumber === null ? $invoiceId : max($highestNumber, $invoiceId);
                continue;
            }

            $storedNumber = $invoice->getStoredInvoiceNumber();
            if ($storedNumber === null) {
                continue;
            }

            $prefix = preg_quote($invoiceSequence->getSequence(), '/');
            if (preg_match('/^'.$prefix.'(\d+)$/', $storedNumber, $matches) !== 1) {
                continue;
            }

            $parsedNumber = (int) $matches[1];
            $highestNumber = $highestNumber === null ? $parsedNumber : max($highestNumber, $parsedNumber);
        }

        if ($highestNumber !== null) {
            return $highestNumber + 1;
        }

        $existingInvoicesCount = count($invoices);
        if ($existingInvoicesCount > 0) {
            return $invoiceSequence->getFirstNumberValue() + $existingInvoicesCount;
        }

        return $invoiceSequence->getFirstNumberValue();
    }

    public function existsOpenTransferInvoiceWithVariableSymbolForBankAccount(
        int $bankAccountId,
        VariableSymbol $variableSymbol,
        ?int $excludeInvoiceId = null,
    ): bool {
        $qb = $this->createQueryBuilder('entity')
            ->select('COUNT(entity.id)')
            ->where('IDENTITY(entity.bankAccount) = :bankAccountId')
            ->andWhere('entity.variableSymbol = :variableSymbol')
            ->andWhere('entity.paymentType = :paymentType')
            ->andWhere('entity.state != :cancelledState')
            ->andWhere('entity.closedAt IS NULL')
            ->setParameter('bankAccountId', $bankAccountId)
            ->setParameter('variableSymbol', $variableSymbol)
            ->setParameter('paymentType', InvoicePaymentType::TRANSFER->value)
            ->setParameter('cancelledState', InvoiceState::CANCELLED);

        if ($excludeInvoiceId !== null) {
            $qb->andWhere('entity.id != :excludeInvoiceId')
                ->setParameter('excludeInvoiceId', $excludeInvoiceId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function existsPairedInvoiceForBankAccount(int $bankAccountId): bool
    {
        return (int) $this->createQueryBuilder('entity')
            ->select('COUNT(entity.id)')
            ->where('IDENTITY(entity.bankAccount) = :bankAccountId')
            ->andWhere('entity.transaction.id IS NOT NULL')
            ->setParameter('bankAccountId', $bankAccountId)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /** @return Invoice[] */
    public function findOpenTransferInvoicesInSequence(InvoiceSequence $invoiceSequence): array
    {
        return $this->createQueryBuilder('entity')
            ->where('entity.sequence = :sequence')
            ->andWhere('entity.paymentType = :paymentType')
            ->andWhere('entity.state != :cancelledState')
            ->andWhere('entity.closedAt IS NULL')
            ->setParameter('sequence', $invoiceSequence)
            ->setParameter('paymentType', InvoicePaymentType::TRANSFER->value)
            ->setParameter('cancelledState', InvoiceState::CANCELLED)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param  int[]     $sequenceIds
     * @return Invoice[]
     */
    public function findOpenTransferInvoicesBySequenceIds(array $sequenceIds): array
    {
        if ($sequenceIds === []) {
            return [];
        }

        return $this->createQueryBuilder('entity')
            ->where('IDENTITY(entity.sequence) IN (:sequenceIds)')
            ->andWhere('entity.paymentType = :paymentType')
            ->andWhere('entity.state != :cancelledState')
            ->andWhere('entity.closedAt IS NULL')
            ->andWhere('entity.variableSymbol IS NOT NULL')
            ->setParameter('sequenceIds', $sequenceIds)
            ->setParameter('paymentType', InvoicePaymentType::TRANSFER->value)
            ->setParameter('cancelledState', InvoiceState::CANCELLED)
            ->getQuery()
            ->getResult();
    }

    /** @return Invoice[] */
    public function findOpenTransferInvoicesForBankAccount(int $bankAccountId): array
    {
        return $this->createQueryBuilder('entity')
            ->where('IDENTITY(entity.bankAccount) = :bankAccountId')
            ->andWhere('entity.paymentType = :paymentType')
            ->andWhere('entity.state != :cancelledState')
            ->andWhere('entity.closedAt IS NULL')
            ->andWhere('entity.variableSymbol IS NOT NULL')
            ->setParameter('bankAccountId', $bankAccountId)
            ->setParameter('paymentType', InvoicePaymentType::TRANSFER->value)
            ->setParameter('cancelledState', InvoiceState::CANCELLED)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param  int[]     $unitIds
     * @return Invoice[]
     */
    public function findOpenTransferInvoicesByUnits(array $unitIds): array
    {
        if ($unitIds === []) {
            return [];
        }

        return $this->createQueryBuilder('entity')
            ->innerJoin('entity.sequence', 'sequence')
            ->where('sequence.unit IN (:unitIds)')
            ->andWhere('entity.paymentType = :paymentType')
            ->andWhere('entity.state != :cancelledState')
            ->andWhere('entity.closedAt IS NULL')
            ->andWhere('entity.variableSymbol IS NOT NULL')
            ->setParameter('unitIds', $unitIds)
            ->setParameter('paymentType', InvoicePaymentType::TRANSFER->value)
            ->setParameter('cancelledState', InvoiceState::CANCELLED)
            ->getQuery()
            ->getResult();
    }
}
