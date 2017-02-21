<?php

namespace Model\Payment\Repositories;

use Model\Payment\BankAccount;
use Model\Skautis\Mapper;
use Skautis\Skautis;

class BankAccountRepository implements IBankAccountRepository
{

    /** @var Skautis */
    private $skautis;

    /** @var Mapper */
    private $mapper;

    /**
     * BankAccountRepository constructor.
     * @param Skautis $skautis
     * @param Mapper $mapper
     */
    public function __construct(Skautis $skautis, Mapper $mapper)
    {
        $this->skautis = $skautis;
        $this->mapper = $mapper;
    }

    public function findByUnit(int $unitId) : array
    {
        $skautisId = $this->mapper->getSkautisId($unitId, Mapper::UNIT);

        if($skautisId === NULL) {
            return [];
        }

        $accounts = $this->skautis->org->AccountAll([
            'ID_Unit' => $skautisId,
            'IsValid' => TRUE
        ]);

        $result = [];
        foreach($accounts as $account) {
            $result[] = new BankAccount($account->DisplayName);
        }

        return $result;
    }

}