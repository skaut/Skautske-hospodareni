<?php

namespace Model\Travel\Repositories;

use Doctrine\ORM\EntityManager;
use Model\Travel\Contract;
use Model\Travel\ContractNotFoundException;

final class ContractRepository implements IContractRepository
{

    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function find(int $id): Contract
    {
        $contract = $this->em->find(Contract::class, $id);

        if($contract === NULL) {
            throw new ContractNotFoundException("Contract with id #$id not found");
        }

        return $contract;
    }

    public function findByUnit(int $unitId): array
    {
        return $this->em->getRepository(Contract::class)->findBy(['unitId' => $unitId]);
    }

    public function save(Contract $contract): void
    {
        $this->em->persist($contract);
        $this->em->flush($contract);
    }

    public function remove(Contract $contract): void
    {
        $this->em->remove($contract);
        $this->em->flush();
    }


}
