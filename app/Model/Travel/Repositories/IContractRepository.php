<?php

declare(strict_types=1);

namespace App\Model\Travel\Repositories;

use App\Model\Travel\Contract;
use App\Model\Travel\ContractNotFound;

interface IContractRepository
{
    /** @throws ContractNotFound */
    public function find(int $id): Contract;

    /** @return Contract[] */
    public function findByUnit(int $unitId): array;

    public function save(Contract $contract): void;

    public function remove(Contract $contract): void;
}
