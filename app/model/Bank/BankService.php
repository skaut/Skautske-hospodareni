<?php

namespace Model;

use Assert\Assert;
use Model\Bank\Fio\FioClient;
use Model\Payment\Group;
use Model\Payment\Payment;
use Model\Payment\Payment\Transaction;
use Model\Bank\Fio\Transaction as BankTransaction;
use Model\Payment\Repositories\IPaymentRepository;
use Model\Utils\Arrays;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Model\Payment\Repositories\IGroupRepository;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BankService
{

    /** @var FioClient */
    private $bank;

    /** @var Cache */
    protected $cache;

    /** @var IGroupRepository */
    private $groups;

    /** @var IPaymentRepository */
    private $payments;

    /** @var BankTable */
    private $table;

    public function __construct(
        BankTable $table,
        IGroupRepository $groups,
        FioClient $bank,
        IPaymentRepository $payments,
        IStorage $storage)
    {
        $this->table = $table;
        $this->groups = $groups;
        $this->bank = $bank;
        $this->payments = $payments;
        $this->cache = new Cache($storage, __CLASS__);
    }

    public function setToken($unitId, $token, $daysback = 14)
    {
        return $token !== "" ? $this->table->setToken($unitId, $token, $daysback) : $this->table->removeToken($unitId);
    }

    public function getInfo($unitId)
    {
        return $this->table->getInfo($unitId);
    }

    /**
     * @param int[] $unitIds
     * @return bool[]
     */
    public function checkCanPair(array $unitIds): array
    {
        $units = [];
        foreach($unitIds as $id) {
            $units[$id] = isset($this->getInfo($id)->token);
        }

        return $units;
    }

    /**
     * Completes payments from info on bank account(s)
     * @param int[] $groupIds
     * @param int|null $daysBack
     * @return int number of paired payments
     */
    public function pairAllGroups(array $groupIds, ?int $daysBack = NULL): int
    {
        Assert::thatAll($groupIds)->integer();
        Assert::that($daysBack)->nullOr()->min(1);

        $groups = $this->groups->findByIds($groupIds);
        $paymentsByGroup = $this->payments->findByMultipleGroups($groupIds);

        $tokens = [];
        $groupsByToken = [];
        $paymentsByToken = [];
        $sinceFallback = [];

        foreach($groups as $id => $group) {
            $unitId = $group->getUnitId();
            $payments = $paymentsByGroup[$group->getId()];
            $payments = array_filter($payments, function (Payment $p) { return $p->canBePaired(); });

            if (empty($payments)) {
                continue;
            }

            if(isset($tokens[$unitId])) {
                $token = $tokens[$unitId];
            } else {
                $bankInfo = $this->getInfo($unitId);
                if(!isset($bankInfo->token)) {
                    continue;
                }

                $token = $tokens[$unitId] = $bankInfo["token"];
                $sinceFallback[$token] = new \DateTimeImmutable("- {$bankInfo->daysback} days");
                $groupsByToken[$token] = [];
                $paymentsByToken[$token] = [];
            }

            $groupsByToken[$token][] = $group;
            $paymentsByToken[$token] = array_merge($paymentsByToken[$token], $paymentsByGroup[$id]);
        }

        if(empty($tokens)) {
            return 0;
        }

        $pairedPayments = [];
        $now = new \DateTimeImmutable();
        foreach($tokens as $token) {
            if($daysBack === NULL) {
                $paired = $this->autoPair($token, $groupsByToken[$token], $paymentsByToken[$token], $sinceFallback[$token], $now);
            } else {
                $paired = $this->pair($token, $paymentsByToken[$token], $now->modify("- $daysBack days"), $now);
            }
            $pairedPayments = array_merge($pairedPayments, $paired);
        }

        $this->payments->saveMany($pairedPayments);

        if($daysBack === NULL) {
            foreach (Arrays::ungroup($groupsByToken) as $group) {
                /* @var $group Group */
                $group->updateLastPairing($now);
                $this->groups->save($group);
            }
        }

        return count($pairedPayments);
    }

    /**
     * @param string $token
     * @param Group[] $groups
     * @param Payment[] $payments
     * @param \DateTimeImmutable $fallbackSince
     * @param \DateTimeImmutable $until
     * @return Payment[]
     */
    private function autoPair(string $token, array $groups, array $payments, \DateTimeImmutable $fallbackSince, \DateTimeImmutable $until): array
    {
        $furthestPairing = min(array_map(function (Group $g) {
            return $g->getLastPairing() ?? $g->getCreatedAt();
        }, $groups));
        return $this->pair($token, $payments, $furthestPairing ?? $fallbackSince, $until);
    }

    /**
     * @param string $token
     * @param Payment[] $payments
     * @param \DateTimeImmutable $since
     * @param \DateTimeImmutable $until
     * @return Payment[]
     */
    private function pair(string $token, array $payments, \DateTimeImmutable $since, \DateTimeImmutable $until): array
    {
        $transactions = $this->bank->getTransactions($since, $until, $token);
        return $this->markPaymentsAsComplete($transactions, $payments);
    }

    /**
     * @param BankTransaction[] $transactions
     * @param Payment[] $payments
     * @return Payment[]
     */
    private function markPaymentsAsComplete(array $transactions, array $payments): array
    {
        $payments = array_filter($payments, function (Payment $p) { return $p->canBePaired(); });
        $paymentsByVS = Arrays::groupBy($payments, function(Payment $p) { return $p->getVariableSymbol(); });

        $transactions = array_filter($transactions, function(BankTransaction $t) use($paymentsByVS) {
            return $t->getVariableSymbol() !== NULL && isset($paymentsByVS[$t->getVariableSymbol()]);
        });

        $paired = [];
        $now = new \DateTimeImmutable();
        foreach ($transactions as $transaction) {
            foreach($paymentsByVS[$transaction->getVariableSymbol()] as $payment) {
                /* @var $payment Payment */
                if ($payment->getAmount() === $transaction->getAmount()) {
                    $payment->complete($now, new Transaction($transaction->getId(), $transaction->getBankAccount()));
                    $paired[] = $payment;
                }
            }
        }

        return $paired;
    }

    /**
     * @deprecated Use FioClient::getTransactions()
     */
    public function getTransactionsFio($token, $daysBack = 14)
    {
        return $this->bank->getTransactions(
            (new \DateTime())->modify("- $daysBack days"),
            new \DateTime(),
            $token
        );
    }

}

class BankTimeoutException extends \Exception
{

}

class BankTimeLimitException extends \Exception
{

}
