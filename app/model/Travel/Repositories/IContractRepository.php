<?php

namespace Model\Travel\Repositories;

use Model\Travel\Contract;
use Model\Travel\ContractNotFoundException;

interface IContractRepository
{

    /**
     * @param int $id
     * @throws ContractNotFoundException
     * @return Contract
     */
    public function find(int $id): Contract;

    /**
     * @return Contract[]
     */
    public function findByUnit(int $unitId): array;

    public function remove(Contract $contract): void;

}
