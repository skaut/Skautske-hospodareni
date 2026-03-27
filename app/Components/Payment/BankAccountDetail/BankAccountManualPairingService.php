<?php

declare(strict_types=1);

namespace App\Components\Payment\BankAccountDetail;

use App\Model\Bank\BankTransactionAmountMismatch;
use App\Model\Bank\BankTransactionPairingNotAllowed;
use App\Model\Bank\Entity\BankTransaction;
use App\Model\Bank\Repository\BankTransactionRepository;
use App\Model\Bank\Services\BankTransactionPairingService;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Payment\PaymentNotFound;
use App\Model\Payment\Repositories\IPaymentRepository;
use DateTimeImmutable;
use InvalidArgumentException;

use function in_array;

final class BankAccountManualPairingService
{
    public function __construct(
        private readonly BankTransactionRepository $transactions,
        private readonly IPaymentRepository $payments,
        private readonly InvoiceRepository $invoices,
        private readonly BankTransactionPairingService $pairingService,
    ) {
    }

    /**
     * @param int[] $accessibleGroupIds
     *
     * @throws BankTransactionAmountMismatch
     * @throws BankTransactionPairingNotAllowed
     */
    public function pairTransactionToPayment(
        int $accountId,
        string $transactionKey,
        int $paymentId,
        array $accessibleGroupIds,
        ?string $pairedBy,
    ): BankAccountManualPairingOutcome {
        $transaction = $this->findTransactionForAccount($accountId, $transactionKey);

        try {
            $payment = $this->payments->find($paymentId);
        } catch (PaymentNotFound) {
            throw new InvalidArgumentException('Platba nebyla nalezena.');
        }

        if (! in_array($payment->getGroupId(), $accessibleGroupIds, true)) {
            throw new InvalidArgumentException('Platbu nelze v tomto scope bankovního účtu párovat.');
        }

        $result = $this->pairingService->pairPaymentManually(
            $transaction,
            $payment,
            new DateTimeImmutable(),
            $pairedBy,
        );

        return new BankAccountManualPairingOutcome(
            'Bankovní transakce byla ručně spárována s platbou.',
            $result->getWarnings(),
        );
    }

    /**
     * @param int[] $readableUnitIds
     *
     * @throws BankTransactionAmountMismatch
     * @throws BankTransactionPairingNotAllowed
     */
    public function pairTransactionToInvoice(
        int $accountId,
        string $transactionKey,
        int $invoiceId,
        array $readableUnitIds,
        ?string $pairedBy,
    ): BankAccountManualPairingOutcome {
        $transaction = $this->findTransactionForAccount($accountId, $transactionKey);
        $invoice = $this->invoices->findAccessibleByUnits($invoiceId, $readableUnitIds);

        if (! $invoice instanceof Invoice) {
            throw new InvalidArgumentException('Faktura nebyla nalezena.');
        }

        $result = $this->pairingService->pairInvoiceManually(
            $transaction,
            $invoice,
            new DateTimeImmutable(),
            $pairedBy,
        );

        return new BankAccountManualPairingOutcome(
            'Bankovní transakce byla ručně spárována s fakturou.',
            $result->getWarnings(),
        );
    }

    private function findTransactionForAccount(int $accountId, string $transactionKey): BankTransaction
    {
        $transaction = $this->transactions->findByTransactionKey($transactionKey);
        if ($transaction === null || $transaction->getBankAccount()->getId() !== $accountId) {
            throw new InvalidArgumentException('Bankovní transakce nebyla nalezena.');
        }

        return $transaction;
    }
}
