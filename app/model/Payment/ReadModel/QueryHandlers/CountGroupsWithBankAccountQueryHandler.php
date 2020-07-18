<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Doctrine\ORM\EntityManager;
use Model\Payment\ReadModel\Queries\CountGroupsWithBankAccountQuery;

final class CountGroupsWithBankAccountQueryHandler
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function __invoke(CountGroupsWithBankAccountQuery $query) : int
    {
        return (int) $this->entityManager
            ->createQuery(/** @lang DQL */ 'SELECT COUNT(g) FROM Model\Payment\Group g WHERE g.bankAccount.id = :id')
            ->setParameter('id', $query->getBankAccountId()->toInt())
            ->getSingleScalarResult();
    }
}
