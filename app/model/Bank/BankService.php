<?php

namespace Model;

use Nette\Caching\Cache;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BankService extends BaseService {

    /**
     *
     * @var \Nette\Caching\Cache
     */
    protected $cache;

    public function __construct(\Nette\Caching\IStorage $storage, $connection = NULL) {
        parent::__construct(NULL, $connection);
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
        if (!$bakInfo->token) {
            return FALSE;
        }
        $payments = $this->filterVS($ps->getAll($groupId === NULL ? array_keys($ps->getGroups($unitId)) : $groupId, FALSE));
        $transactions = $this->getTransactionsFio($bakInfo->token, $bakInfo->daysback);
        if (!$transactions) {
            return FALSE;
        }
        //dump($this->filterVS($transactions));dump($payments);die();
        $cnt = 0;
        foreach ($this->filterVS($transactions) as $t) {
            foreach ($payments as $p) {
                if ($t['vs'] == $p['vs'] && $t['amount'] == $p['amount']) {
                    $cnt += $ps->completePayment($p->id, $t['id']);
                }
            }
        }
        return $cnt;
    }

    public function getTransactionsFio($token, $daysBack = 14) {
        $dateStart = date("Y-m-d", strtotime("-" . (int) $daysBack . " day"));
        $dateEnd = date("Y-m-d");
        $cacheKey = __FUNCTION__ . $token;
        if (!($transactions = $this->cache->load($cacheKey))) {
            //$url = WWW_DIR . "/test-transactions.json";
            $url = "https://www.fio.cz/ib_api/rest/periods/$token/$dateStart/$dateEnd/transactions.json";
            $file = $this->getTransactions($url);
            //$file = file_get_contents($url);
            if (!$file) {
                return FALSE;
            }
            $transactions = array();
            $transactionList = json_decode($file)->accountStatement->transactionList;
            if ($transactionList !== NULL) {
                //dump($transactionList->transaction);die();
                foreach ($transactionList->transaction as $k => $t) {
                    $transactions[$k]['id'] = $t->column22->value;
                    $transactions[$k]['date'] = $t->column0->value;
                    $transactions[$k]['amount'] = $t->column1->value;
                    $transactions[$k]['prociucet'] = $t->column2 !== NULL ? $t->column2->value . "/" . $t->column3->value : "";
                    $transactions[$k]['user'] = $t->column7 !== NULL ? $t->column7->value : ($t->column10 != NULL ? $t->column10->value : "");
                    $transactions[$k]['ks'] = isset($t->column4) ? $t->column4->value : NULL;
                    $transactions[$k]['vs'] = isset($t->column5) ? $t->column5->value : NULL;
                    $transactions[$k]['note'] = isset($t->column16) ? $t->column16->value : NULL;
                }
            }
            $this->cache->save($cacheKey, $transactions, array(Cache::EXPIRE => '35 seconds',));
        }
        return $transactions;
    }

    protected function getTransactions($url, $timeout = 3) {
        $ch = curl_init($url);
        // Include header in result? (0 = yes, 1 = no)
        curl_setopt($ch, CURLOPT_HEADER, 1);
        // Should cURL return or print out the data? (true = return, false = print)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $response = curl_exec($ch);
        $header = substr($response, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        $body = substr($response, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        if ($header == NULL && curl_errno($ch) == 28) {
            throw new BankTimeoutException();
        }
        if (\Nette\Utils\Strings::contains($header, "HTTP/1.1 409 Conflict")) {
            throw new BankTimeLimitException();
        }
        return $body;
    }

    protected function filterVS($arr) {
        return array_filter($arr, function ($t) {
            return array_key_exists("vs", $t) && $t['vs'] != NULL;
        });
    }

}

class BankTimeoutException extends \Exception {
    
}

class BankTimeLimitException extends \Exception {
    
}
