<?php

namespace Model\Payment\Repositories;

use Doctrine\ORM\EntityManager;
use Model\Payment\BankAccount;
use Model\Payment\BankAccountNotFoundException;

class BankAccountRepository implements IBankAccountRepository
{

    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function find(int $id): BankAccount
    {
        $account = $this->entityManager->find(BankAccount::class, $id);

        if($account === NULL) {
            throw new BankAccountNotFoundException();
        }

        return $account;
    }

    public function save(BankAccount $account): void
    {
        $this->entityManager->persist($account);
        $this->entityManager->flush();
    }

    public function findByUnit(int $unitId): array
    {
        return $this->entityManager->getRepository(BankAccount::class)->findBy(['unitId' => $unitId]);
    }


    public function remove(BankAccount $account): void
    {
        $this->entityManager->remove($account);
        $this->entityManager->flush();
    }

}
