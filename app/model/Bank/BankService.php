<?php

namespace Model;

use Model\Bank\Fio\FioClient;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BankService extends BaseService {

	/** @var FioClient */
	private $bank;

    /**
     *
     * @var \Nette\Caching\Cache
     */
    protected $cache;

    public function __construct(\Nette\Caching\IStorage $storage, FioClient $bank,\Dibi\Connection $connection = NULL) {
        parent::__construct(NULL, $connection);
		$this->bank = $bank;
        $this->cache = new \Nette\Caching\Cache($storage, __CLASS__);
    }

    public function setToken($unitId, $token, $daysback = 14) {
        return $token !== "" ? $this->table->setToken($unitId, $token, $daysback) : $this->table->removeToken($unitId);
    }

    public function getInfo($unitId) {
        return $this->table->getInfo($unitId);
    }

    public function pairPayments(PaymentService $ps, $unitId, $groupId = NULL) {
        $bakInfo = $this->getInfo($unitId);
        if (!isset($bakInfo->token)) {
            return FALSE;
        }
        $payments = $ps->getAll($groupId === NULL ? array_keys($ps->getGroups($unitId)) : $groupId, FALSE);


		$transactions = $this->bank->getTransactions(
			(new \DateTime())->modify("- {$bakInfo->daysback} days"),
			new \DateTime(),
			$bakInfo->token
		);

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
