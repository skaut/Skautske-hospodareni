<?php

namespace Model;

use Model\Bank\Fio\FioClient;
use Model\Payment\Repositories\IGroupRepository;
use Dibi\Connection;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BankService extends BaseService {

	/** @var FioClient */
	private $bank;

    /** @var IGroupRepository */
    private $groups;

    public function __construct(Connection $connection, FioClient $bank, IGroupRepository $groups)
	{
        parent::__construct(NULL, $connection);
		$this->bank = $bank;
        $this->groups = $groups;
    }

    public function setToken($unitId, $token, $daysback = 14) {
        return $token !== "" ? $this->table->setToken($unitId, $token, $daysback) : $this->table->removeToken($unitId);
    }

    public function getInfo($unitId) {
        return $this->table->getInfo($unitId);
    }

	/**
	 * Completes payments on bank account
	 * @param PaymentService $ps
	 * @param int $unitId
	 * @param int $groupId
	 * @param int|NULL $daysBack
	 * @return int|FALSE
	 */
    public function pairPayments(PaymentService $ps, $unitId, $groupId, $daysBack = NULL)
	{
        $bakInfo = $this->getInfo($unitId);
        if (!isset($bakInfo->token)) {
            return FALSE;
        }

        $payments = $ps->getAll($groupId, FALSE);

        $autoPairing = !$daysBack;
		$group = $this->groups->find($groupId);
        if($autoPairing) {
			$lastPairing = $group->getLastPairing() ?: $group->getCreatedAt();
			if($lastPairing) {
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

        $result = $this->markPaymentsAsComplete($ps, $transactions, $payments);

        if($autoPairing) {
        	$group->updateLastPairing(new \DateTimeImmutable());
        	$this->groups->save($group);
		}
		return $result;
    }

	/**
	 * @param PaymentService $ps
	 * @param array $transactions
	 * @param array $payments
	 * @return int|FALSE
	 */
    private function markPaymentsAsComplete(PaymentService $ps, array $transactions, array $payments)
	{
		if (!$transactions) {
			return FALSE;
		}

		/**
		 * We'll need payments indexed by VS
		 */
		$paymentsWithVS = [];
		foreach($payments as $payment) {
			if($payment['vs']) {
				$paymentsWithVS[$payment['vs']] = $payment;
			}
		}

		$cnt = 0;
		foreach($transactions as $transaction) {

			// Skip transactions w/o variable symbol
			if(!$transaction->getVariableSymbol()) {
				continue;
			}

			$payment = isset($paymentsWithVS[$transaction->getVariableSymbol()])
				? $paymentsWithVS[$transaction->getVariableSymbol()]
				: NULL;

			if($payment && $payment['amount'] == $transaction->getAmount()) {
				$cnt += $ps->completePayment(
					$payment->id,
					$transaction->getId(),
					$transaction->getBankAccount());
			}
		}

		return $cnt;
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

class BankTimeoutException extends \Exception {

}

class BankTimeLimitException extends \Exception {

}
