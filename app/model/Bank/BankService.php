<?php

declare(strict_types=1);

namespace Model;

use Assert\Assert;
use Cake\Chronos\Date;
use DateTimeImmutable;
use Model\Bank\Fio\Transaction as BankTransaction;
use Model\DTO\Payment\PairingResult;
use Model\Google\InvalidOAuth;
use Model\Payment\Fio\IFioClient;
use Model\Payment\Group;
use Model\Payment\Payment;
use Model\Payment\Payment\Transaction;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\Repositories\IPaymentRepository;
use Model\Utils\Arrays;

use function array_filter;
use function array_keys;
use function array_map;
use function assert;
use function count;
use function is_array;
use function is_int;
use function min;

class BankService
{
    public const DAYS_BACK_DEFAULT = 60;

    public function __construct(
        private IGroupRepository $groups,
        private IFioClient $bank,
        private IPaymentRepository $payments,
        private IBankAccountRepository $bankAccounts,
    ) {
    }

    /**
     * Completes payments from info on bank account(s)
     *
     * @param  int[] $groupIds
     *
     * @return PairingResult[] Description of paired payments
     *
     * @throws BankTimeLimit
     * @throws BankTimeout
     * @throws InvalidOAuth
     */
    public function pairAllGroups(array $groupIds, int|null $daysBack = null): array
    {
        Assert::thatAll($groupIds)->integer();
        Assert::that($daysBack)->nullOr()->min(1);

        $foundGroups = $this->groups->findByIds($groupIds);

        $groupsByAccount = Arrays::groupBy(
            $foundGroups,
            function (Group $g) {
                return $g->getBankAccountId();
            },
            true,
        );

        $now            = new DateTimeImmutable();
        $pairedCount    = 0;
        $pairingResults = [];

        foreach ($groupsByAccount as $bankAccountId => $groups) {
            assert(is_int($bankAccountId) && is_array($groups));

            $bankAccount = $this->bankAccounts->find($bankAccountId);

            if ($bankAccount->getToken() === null) {
                continue;
            }

            $payments = $this->payments->findByMultipleGroups(array_keys($groups));
            $payments = array_filter(
                $payments,
                function (Payment $p) {
                    return $p->canBePaired();
                },
            );

            if (empty($payments)) {
                continue;
            }

            $pairSince = $daysBack === null ? $this->resolvePairingIntervalStart($groups) : Date::today()->subDays($daysBack);

            $transactions = $this->bank->getTransactions($pairSince, new Date($now), $bankAccount);
            $paired       = $this->markPaymentsAsComplete($transactions, $payments);

            $this->payments->saveMany($paired);
            $pairedCount += count($paired);

            $pairingResults[] = new PairingResult($bankAccount->getName(), $pairSince, $now, $pairedCount);

            if ($daysBack !== null) {
                continue;
            }

            $this->updateLastPairing($groups, $now);
        }

        return $pairingResults;
    }

    /** @param Group[] $groups */
    private function updateLastPairing(array $groups, DateTimeImmutable $time): void
    {
        foreach ($groups as $group) {
            $group->updateLastPairing($time);
            $this->groups->save($group);
        }
    }

    /** @param Group[] $groups */
    private function resolvePairingIntervalStart(array $groups): Date
    {
        $defaultStart = Date::today()->subDays(self::DAYS_BACK_DEFAULT);

        if ($groups === []) {
            return $defaultStart;
        }

        return new Date(
            min(
                array_map(fn (Group $g) => $g->getLastPairing() ?? $defaultStart, $groups),
            ),
        );
    }

    /**
     * @param BankTransaction[] $transactions
     * @param Payment[]         $payments
     *
     * @return Payment[]
     */
    private function markPaymentsAsComplete(array $transactions, array $payments): array
    {
        $paymentsByVS = Arrays::groupBy(
            $payments,
            function (Payment $p) {
                return $p->getVariableSymbol()->toInt();
            },
        );

        $transactions = array_filter(
            $transactions,
            function (BankTransaction $t) use ($paymentsByVS) {
                return $t->getVariableSymbol() !== null && isset($paymentsByVS[$t->getVariableSymbol()]);
            },
        );

        $paired = [];
        $now    = new DateTimeImmutable();
        foreach ($transactions as $transaction) {
            foreach ($paymentsByVS[$transaction->getVariableSymbol()] as $offset => $payment) {
                assert($payment instanceof Payment);
                if ($payment->getAmount() !== $transaction->getAmount()) {
                    continue;
                }

                $payment->pairWithTransaction($now, Transaction::fromFioTransaction($transaction));
                $paired[] = $payment;
                unset($paymentsByVS[$transaction->getVariableSymbol()][$offset]);
            }
        }

        return $paired;
    }
}
