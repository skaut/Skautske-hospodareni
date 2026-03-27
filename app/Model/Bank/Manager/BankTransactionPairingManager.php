<?php

declare(strict_types=1);

namespace App\Model\Bank\Manager;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Entity\BankTransaction;
use App\Model\Bank\Entity\BankTransactionPairing;
use App\Model\Bank\Enum\BankTransactionPairingMode;
use App\Model\Bank\Repository\BankTransactionPairingRepository;
use App\Model\Common\Embeddable\Transaction;
use App\Model\Infrastructure\Manager\AbstractManager;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Payment\Payment;
use DateTimeImmutable;

class BankTransactionPairingManager extends AbstractManager
{
    public function __construct(
        \Doctrine\ORM\EntityManagerInterface $entityManager,
        private readonly BankTransactionPairingRepository $pairings,
    ) {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return BankTransactionPairing::class;
    }

    public function pairPaymentWithoutFlush(
        BankTransaction $bankTransaction,
        Payment $payment,
        DateTimeImmutable $pairedAt,
        BankTransactionPairingMode $pairingMode = BankTransactionPairingMode::AUTOMATIC,
        ?string $pairedBy = null,
    ): bool {
        if ($this->pairings->findActiveByTransaction($bankTransaction) !== null) {
            return false;
        }

        if ($this->pairings->findActiveByPayment($payment) !== null) {
            return false;
        }

        $payment->pairWithTransaction($pairedAt, Transaction::fromBankTransaction($bankTransaction));
        $this->em->persist($payment);
        $this->em->persist($this->createPaymentPairing($bankTransaction, $payment, $pairedAt, $pairingMode, $pairedBy));

        return true;
    }

    public function pairInvoiceWithoutFlush(
        BankTransaction $bankTransaction,
        Invoice $invoice,
        DateTimeImmutable $pairedAt,
        BankTransactionPairingMode $pairingMode = BankTransactionPairingMode::AUTOMATIC,
        ?string $pairedBy = null,
    ): bool {
        if ($this->pairings->findActiveByTransaction($bankTransaction) !== null) {
            return false;
        }

        if ($this->pairings->findActiveByInvoice($invoice) !== null) {
            return false;
        }

        if (! $invoice->pairWithBankTransaction($pairedAt, $pairedBy, Transaction::fromBankTransaction($bankTransaction))) {
            return false;
        }

        $this->em->persist($invoice);
        $this->em->persist($this->createInvoicePairing($bankTransaction, $invoice, $pairedAt, $pairingMode, $pairedBy));

        return true;
    }

    public function cancelPaymentPairingWithoutFlush(
        Payment $payment,
        DateTimeImmutable $cancelledAt,
        ?string $cancelledBy = null,
        ?string $reason = null,
    ): bool {
        $pairing = $this->pairings->findActiveByPayment($payment);

        if ($pairing === null || ! $payment->unpairTransaction()) {
            return false;
        }

        $pairing->cancel($cancelledAt, $cancelledBy, $reason);
        $this->em->persist($payment);
        $this->em->persist($pairing);

        return true;
    }

    public function cancelInvoicePairingWithoutFlush(
        Invoice $invoice,
        DateTimeImmutable $cancelledAt,
        ?string $cancelledBy = null,
        ?string $reason = null,
    ): bool {
        $pairing = $this->pairings->findActiveByInvoice($invoice);

        if ($pairing === null || ! $invoice->unpairBankTransaction()) {
            return false;
        }

        $pairing->cancel($cancelledAt, $cancelledBy, $reason);
        $this->em->persist($invoice);
        $this->em->persist($pairing);

        return true;
    }

    private function createPaymentPairing(
        BankTransaction $bankTransaction,
        Payment $payment,
        DateTimeImmutable $pairedAt,
        BankTransactionPairingMode $pairingMode,
        ?string $pairedBy,
    ): BankTransactionPairing {
        [$historicalBankAccountId, $historicalBankAccountName, $historicalAccountNumber, $historicalBankCode] = $this->createHistoricalSnapshot($bankTransaction->getBankAccount());

        return BankTransactionPairing::forPayment(
            $bankTransaction,
            $bankTransaction->getTransactionKey(),
            $payment,
            $pairingMode,
            $pairedAt,
            $pairedBy,
            $historicalBankAccountId,
            $historicalBankAccountName,
            $historicalAccountNumber,
            $historicalBankCode,
        );
    }

    private function createInvoicePairing(
        BankTransaction $bankTransaction,
        Invoice $invoice,
        DateTimeImmutable $pairedAt,
        BankTransactionPairingMode $pairingMode,
        ?string $pairedBy,
    ): BankTransactionPairing {
        [$historicalBankAccountId, $historicalBankAccountName, $historicalAccountNumber, $historicalBankCode] = $this->createHistoricalSnapshot($bankTransaction->getBankAccount());

        return BankTransactionPairing::forInvoice(
            $bankTransaction,
            $bankTransaction->getTransactionKey(),
            $invoice,
            $pairingMode,
            $pairedAt,
            $pairedBy,
            $historicalBankAccountId,
            $historicalBankAccountName,
            $historicalAccountNumber,
            $historicalBankCode,
        );
    }

    /** @return array{0: ?int, 1: ?string, 2: ?string, 3: ?string} */
    private function createHistoricalSnapshot(?BankAccount $bankAccount): array
    {
        if ($bankAccount === null) {
            return [null, null, null, null];
        }

        return [
            $bankAccount->getId(),
            $bankAccount->getName(),
            $bankAccount->getNumber()->getNumberWithPrefix(),
            $bankAccount->getNumber()->getBankCode(),
        ];
    }
}
