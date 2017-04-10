<?php

namespace Model;

use Consistence\Type\ArrayType\ArrayType;
use Consistence\Type\ArrayType\KeyValuePair;
use Model\Bank\Fio\FioClient;
use Model\Payment\Payment;
use Model\Payment\Payment\Transaction;
use Model\Bank\Fio\Transaction as BankTransaction;
use Model\Payment\Repositories\IPaymentRepository;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Model\Payment\Repositories\IGroupRepository;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BankService extends BaseService
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
        parent::__construct();

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
     * Completes payments on bank account
     * @param int $unitId
     * @param int $groupId
     * @param int|NULL $daysBack
     * @return int
     */
    public function pairPayments(int $unitId, int $groupId, ?int $daysBack): int
    {
        $bakInfo = $this->getInfo($unitId);
        if (!isset($bakInfo->token)) {
            return 0;
        }

        $autoPairing = $daysBack === NULL;
        $group = $this->groups->find($groupId);
        if ($autoPairing) {
            $lastPairing = $group->getLastPairing() ?? $group->getCreatedAt();
            if ($lastPairing !== NULL) {
                $daysBack = $lastPairing->diff(new \DateTime())->days + 3;
            } else {
                $daysBack = $bakInfo->daysback;
            }
        }

        $transactions = $this->bank->getTransactions(
            (new \DateTime())->modify("- $daysBack days"),
            new \DateTime(),
            $bakInfo->token
        );

        $result = $this->markPaymentsAsComplete($transactions, $this->payments->findByGroup($groupId));

        if ($autoPairing) {
            $group->updateLastPairing(new \DateTimeImmutable());
            $this->groups->save($group);
        }
        return $result;
    }

    /**
     * @param BankTransaction[] $transactions
     * @param Payment[] $payments
     * @return int
     */
    private function markPaymentsAsComplete(array $transactions, array $payments): int
    {
        if (empty($transactions)) {
            return 0;
        }

        $payments = ArrayType::filterValuesByCallback($payments, function(Payment $payment) {
            return !$payment->isClosed() && $payment->getVariableSymbol() !== NULL;
        });

        if(empty($payments)) {
            return 0;
        }

        $paymentsWithVS = ArrayType::mapByCallback($payments, function(KeyValuePair $pair) {
            $value = $pair->getValue(); /* @var $value Payment */
            return new KeyValuePair($value->getVariableSymbol(), $value);
        });

        $paired = [];
        $now = new \DateTimeImmutable();
        foreach ($transactions as $transaction) {
            $vs = $transaction->getVariableSymbol();

            /* @var $paymentsWithVS Payment[] */
            if ($vs === NULL || !isset($paymentsWithVS[$vs])) {
                continue;
            }

            $payment = $paymentsWithVS[$vs];

            if ($payment->getAmount() === $transaction->getAmount()) {
                $payment->complete($now, new Transaction($transaction->getId(), $transaction->getBankAccount()));
                $paired[] = $payment;
            }
        }

        $this->payments->saveMany($paired);

        return count($paired);
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
