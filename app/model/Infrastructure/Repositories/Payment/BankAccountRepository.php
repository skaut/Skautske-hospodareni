<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Payment;

use Doctrine\ORM\EntityManager;
use Model\Payment\BankAccount;
use Model\Payment\BankAccountNotFound;
use Model\Payment\Repositories\IBankAccountRepository;
use function array_unique;
use function count;

final class BankAccountRepository implements IBankAccountRepository
{
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function find(int $id) : BankAccount
    {
        $account = $this->entityManager->find(BankAccount::class, $id);

        if ($account === null) {
            throw new BankAccountNotFound();
        }

        return $account;
    }

    /**
     * {@inheritDoc}
     */
    public function findByIds(array $ids) : array
    {
        $ids = array_unique($ids);

        $accounts = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(BankAccount::class, 'a', 'a.id')
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        if (count($accounts) !== count($ids)) {
            throw new BankAccountNotFound();
        }

        return $accounts;
    }

    public function save(BankAccount $account) : void
    {
        $this->entityManager->persist($account);
        $this->entityManager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function findByUnit(int $unitId) : array
    {
        return $this->entityManager->getRepository(BankAccount::class)->findBy(['unitId' => $unitId]);
    }

    public function remove(BankAccount $account) : void
    {
        $this->entityManager->remove($account);
        $this->entityManager->flush();
    }
}
