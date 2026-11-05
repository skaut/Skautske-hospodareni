<?php

declare(strict_types=1);

namespace App\Model\Payment\Repositories;

use App\Model\Payment\Payment;
use App\Model\Payment\PaymentNotFound;
use App\Model\Payment\Summary;
use App\Model\Payment\VariableSymbol;
use Cake\Chronos\ChronosDate;

interface IPaymentRepository
{
    /** @throws PaymentNotFound */
    public function find(int $id): Payment;

    /** @return Payment[] */
    public function findByGroup(int $groupId): array;

    /**
     * Payments of the group whose automatic reminder is due today, see {@see Payment::isAutomaticReminderDue()}.
     *
     * @return Payment[]
     */
    public function findDueAutomaticReminders(int $groupId, int $daysAfterDueDate, ChronosDate $today): array;

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

    /**
     * Unpaid payments with a variable symbol, the ones bank pairing can still complete.
     *
     * @param int[] $groupIds
     *
     * @return Payment[]
     */
    public function findOpenByGroups(array $groupIds): array;

    public function existsPairedPaymentForBankAccount(int $bankAccountId): bool;
}
