<?php

declare(strict_types=1);

namespace App\Model\Payment\Repositories;

use App\Model\Payment\Payment;
use App\Model\Payment\PaymentNotFound;
use App\Model\Payment\Summary;
use App\Model\Payment\VariableSymbol;

interface IPaymentRepository
{
    /** @throws PaymentNotFound */
    public function find(int $id): Payment;

    /** @return Payment[] */
    public function findByGroup(int $groupId): array;

    /**
     * @param int[] $groupIds
     *
     * @return Payment[]
     */
    public function findByReminder(array $groupIds): array;

    /**
     * @param int[] $groupIds
     *
     * @return Payment[]
     */
    public function findByMultipleGroups(array $groupIds): array;

    /**
     * @param int[] $groupIds
     *
     * @return Summary[][]
     */
    public function summarizeByGroup(array $groupIds): array;

    public function save(Payment $payment): void;

    /** @param Payment[] $payments */
    public function saveMany(array $payments): void;

    public function remove(Payment $payment): void;

    public function getMaxVariableSymbol(int $groupId): ?VariableSymbol;

    public function existsPaymentWithVariableSymbolInGroup(
        int $groupId,
        VariableSymbol $variableSymbol,
        ?int $excludePaymentId = null,
    ): bool;

    public function existsOpenPaymentWithVariableSymbolForBankAccount(
        int $bankAccountId,
        VariableSymbol $variableSymbol,
        ?int $excludePaymentId = null,
    ): bool;

    /** @return Payment[] */
    public function findOpenByBankAccount(int $bankAccountId): array;

    public function existsPairedPaymentForBankAccount(int $bankAccountId): bool;
}
