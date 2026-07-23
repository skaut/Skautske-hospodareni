<?php

declare(strict_types=1);

namespace App\Model\Payment\Services;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Payment\Group;
use App\Model\Payment\Payment;
use App\Model\Payment\Repositories\IPaymentRepository;
use App\Model\Payment\VariableSymbol;
use App\Model\Payment\VariableSymbolCollision;

final class VariableSymbolCollisionChecker
{
    public function __construct(
        private IPaymentRepository $payments,
        private InvoiceRepository $invoices,
    ) {
    }

    public function assertUniqueForInvoice(Invoice $invoice, VariableSymbol $variableSymbol): void
    {
        if ($invoice->getPaymentType()->value !== InvoicePaymentType::TRANSFER->value) {
            return;
        }

        $bankAccount = $invoice->getBankAccount();

        if ($bankAccount === null) {
            return;
        }

        if ($this->hasCollision($bankAccount->getId(), $variableSymbol, null, $invoice->hasId() ? $invoice->getId() : null)) {
            throw VariableSymbolCollision::forBankAccount($variableSymbol);
        }
    }

    public function assertUniqueForPayment(Group $group, ?int $excludePaymentId, VariableSymbol $variableSymbol): void
    {
        $bankAccountId = $group->getBankAccountId();

        if ($bankAccountId === null) {
            return;
        }

        if ($this->hasCollision($bankAccountId, $variableSymbol, $excludePaymentId)) {
            throw VariableSymbolCollision::forBankAccount($variableSymbol);
        }
    }

    public function assertGroupCanUseBankAccount(Group $group, ?BankAccount $bankAccount): void
    {
        if ($bankAccount === null) {
            return;
        }

        foreach ($this->payments->findByGroup((int) $group->getId()) as $payment) {
            if (! $payment instanceof Payment || ! $payment->canBePaired()) {
                continue;
            }

            $variableSymbol = $payment->getVariableSymbol();

            if ($variableSymbol === null) {
                continue;
            }

            if ($this->hasCollision($bankAccount->getId(), $variableSymbol, $payment->getId())) {
                throw VariableSymbolCollision::forBankAccount($variableSymbol);
            }
        }
    }

    public function assertSequenceCanUseBankAccount(InvoiceSequence $sequence, ?BankAccount $bankAccount): void
    {
        if ($bankAccount === null) {
            return;
        }

        foreach ($this->invoices->findOpenTransferInvoicesInSequence($sequence) as $invoice) {
            $variableSymbol = $invoice->getVariableSymbol();

            if ($this->hasCollision($bankAccount->getId(), $variableSymbol, null, $invoice->getId())) {
                throw VariableSymbolCollision::forBankAccount($variableSymbol);
            }
        }
    }

    private function hasCollision(
        int $bankAccountId,
        VariableSymbol $variableSymbol,
        ?int $excludePaymentId = null,
        ?int $excludeInvoiceId = null,
    ): bool {
        return $this->payments->existsOpenPaymentWithVariableSymbolForBankAccount($bankAccountId, $variableSymbol, $excludePaymentId)
            || $this->invoices->existsOpenTransferInvoiceWithVariableSymbolForBankAccount($bankAccountId, $variableSymbol, $excludeInvoiceId);
    }
}
