<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\Payment\ReadModel\Queries\CountGroupsWithBankAccountQuery;
use Doctrine\ORM\EntityManager;

final class CountGroupsWithBankAccountQueryHandler
{
    public function __construct(private EntityManager $entityManager)
    {
    }

    public function __invoke(CountGroupsWithBankAccountQuery $query): int
    {
        return (int) $this->entityManager
            ->createQuery(/* @lang DQL */ 'SELECT COUNT(g) FROM App\Model\Payment\Group g WHERE g.bankAccount.id = :id')
            ->setParameter('id', $query->getBankAccountId()->toInt())
            ->getSingleScalarResult();
    }
}
