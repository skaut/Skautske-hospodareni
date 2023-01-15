<?php

declare(strict_types=1);

namespace Model\Payment\Repositories;

use Model\Payment\Payment;
use Model\Payment\PaymentNotFound;
use Model\Payment\Summary;
use Model\Payment\VariableSymbol;

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

    public function getMaxVariableSymbol(int $groupId): VariableSymbol|null;
}
