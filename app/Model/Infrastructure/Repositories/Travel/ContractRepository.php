<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Repositories\Travel;

use App\Model\Travel\Contract;
use App\Model\Travel\ContractNotFound;
use App\Model\Travel\Repositories\IContractRepository;
use Doctrine\ORM\EntityManager;

final class ContractRepository implements IContractRepository
{
    public function __construct(private EntityManager $em)
    {
    }

    public function find(int $id): Contract
    {
        $contract = $this->em->find(Contract::class, $id);

        if ($contract === null) {
            throw new ContractNotFound('Contract with id #'.$id.' not found');
        }

        return $contract;
    }

    /** @return Contract[] */
    public function findByUnit(int $unitId): array
    {
        return $this->em->getRepository(Contract::class)->findBy(['unitId' => $unitId]);
    }

    public function save(Contract $contract): void
    {
        $this->em->persist($contract);
        $this->em->flush();
    }

    public function remove(Contract $contract): void
    {
        $this->em->remove($contract);
        $this->em->flush();
    }
}
