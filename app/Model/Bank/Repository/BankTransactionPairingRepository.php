<?php

declare(strict_types=1);

namespace App\Model\Bank\Repository;

use App\Model\Bank\Entity\BankTransaction;
use App\Model\Bank\Entity\BankTransactionPairing;
use App\Model\Infrastructure\Repository\AbstractRepository;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Payment\Payment;

use function array_values;

/** @extends AbstractRepository<BankTransactionPairing> */
class BankTransactionPairingRepository extends AbstractRepository
{
    public function getEntityClass(): string
    {
        return BankTransactionPairing::class;
    }

    public function findActiveByTransaction(BankTransaction $transaction): ?BankTransactionPairing
    {
        return $this->findActiveByTransactionKey($transaction->getTransactionKey());
    }

    public function findActiveByTransactionKey(string $transactionKey): ?BankTransactionPairing
    {
        return $this->createQueryBuilder('entity')
            ->where('entity.transactionKey = :transactionKey')
            ->andWhere('entity.cancelledAt IS NULL')
            ->setParameter('transactionKey', $transactionKey)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @param list<string> $transactionKeys
     * @return list<BankTransactionPairing>
     */
    public function findActiveByTransactionKeys(array $transactionKeys): array
    {
        if ($transactionKeys === []) {
            return [];
        }

        return array_values(
            $this->createQueryBuilder('entity')
                ->where('entity.transactionKey IN (:transactionKeys)')
                ->andWhere('entity.cancelledAt IS NULL')
                ->setParameter('transactionKeys', $transactionKeys)
                ->getQuery()
                ->getResult(),
        );
    }

    public function findActiveByPayment(Payment $payment): ?BankTransactionPairing
    {
        return $this->createQueryBuilder('entity')
            ->where('entity.payment = :payment')
            ->andWhere('entity.cancelledAt IS NULL')
            ->setParameter('payment', $payment)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveByInvoice(Invoice $invoice): ?BankTransactionPairing
    {
        return $this->createQueryBuilder('entity')
            ->where('entity.invoice = :invoice')
            ->andWhere('entity.cancelledAt IS NULL')
            ->setParameter('invoice', $invoice)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
