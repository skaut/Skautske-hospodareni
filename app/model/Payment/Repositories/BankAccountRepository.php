<?php

namespace Model\Payment\Repositories;

use Model\Payment\BankAccount;
use Model\Skautis\Mapper;
use Skautis\Skautis;

class BankAccountRepository implements IBankAccountRepository
{

    /** @var Skautis */
    private $skautis;

    /**
     * BankAccountRepository constructor.
     * @param Skautis $skautis
     * @param Mapper $mapper
     */
    public function __construct(Skautis $skautis, Mapper $mapper)
    {
        $this->skautis = $skautis;
    }

    public function findByUnit(int $unitSkautisId) : array
    {
        $accounts = $this->skautis->org->AccountAll([
            'ID_Unit' => $unitSkautisId,
            'IsValid' => TRUE,
        ]);

        $result = [];
        foreach($accounts as $account) {
            $result[] = new BankAccount($account->DisplayName, (bool)$account->IsMain);
        }

        return $result;
    }

}
