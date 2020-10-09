<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Travel;

use Doctrine\ORM\EntityManager;
use Model\Travel\Contract;
use Model\Travel\ContractNotFound;
use Model\Travel\Repositories\IContractRepository;

final class ContractRepository implements IContractRepository
{
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function find(int $id) : Contract
    {
        $contract = $this->em->find(Contract::class, $id);

        if ($contract === null) {
            throw new ContractNotFound('Contract with id #' . $id . ' not found');
        }

        return $contract;
    }

    /**
     * @return Contract[]
     */
    public function findByUnit(int $unitId) : array
    {
        return $this->em->getRepository(Contract::class)->findBy(['unitId' => $unitId]);
    }

    public function save(Contract $contract) : void
    {
        $this->em->persist($contract);
        $this->em->flush($contract);
    }

    public function remove(Contract $contract) : void
    {
        $this->em->remove($contract);
        $this->em->flush();
    }
}
