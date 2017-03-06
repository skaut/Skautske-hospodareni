<?php

namespace Model\Payment\Repositories;

use Model\Payment\BankAccount;
use Model\UnitService;
use Skautis\Skautis;

class BankAccountRepository implements IBankAccountRepository
{

    /** @var Skautis */
    private $skautis;

    /** @var UnitService */
    private $units;

    public function __construct(Skautis $skautis, UnitService $units)
    {
        $this->skautis = $skautis;
        $this->units = $units;
    }

    public function findByUnit(int $unitSkautisId) : array
    {
        $unitId = $this->units->getOficialUnit($unitSkautisId)->ID;

        $accounts = $this->skautis->org->AccountAll([
            'ID_Unit' => $unitId,
            'IsValid' => TRUE,
        ]);

        $result = [];
        foreach($accounts as $account) {
            $result[] = new BankAccount($account->DisplayName, (bool)$account->IsMain);
        }

        return $result;
    }

}
