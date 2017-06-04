<?php

namespace Model\Payment\Repositories;

use Model\Payment\Payment;
use Model\Payment\PaymentNotFoundException;

interface IPaymentRepository
{

    /**
	 * @param int $id
	 * @return Payment
	 * @throws PaymentNotFoundException
	 */
    public function find(int $id): Payment;

    /**
     * @param int $groupId
     * @return Payment[]
     */
    public function findByGroup(int $groupId): array;

    /**
     * @param int[] $groupIds
     * @return Payment[][]
     */
    public function findByMultipleGroups(array $groupIds): array;

    public function save(Payment $payment): void;

    /**
     * @param Payment[] $payments
     */
    public function saveMany(array $payments): void;

    public function getMaxVariableSymbol(int $groupId): ?int;

}
