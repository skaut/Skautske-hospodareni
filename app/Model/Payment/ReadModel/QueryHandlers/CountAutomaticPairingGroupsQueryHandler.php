<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Payment\Group;
use App\Model\Payment\ReadModel\Queries\CountAutomaticPairingGroupsQuery;
use Doctrine\ORM\EntityManager;

final class CountAutomaticPairingGroupsQueryHandler
{
    public function __construct(private EntityManager $entityManager)
    {
    }

    public function __invoke(CountAutomaticPairingGroupsQuery $query): int
    {
        return (int) $this->entityManager
            ->createQuery(/* @lang DQL */ 'SELECT COUNT(g) FROM App\Model\Payment\Group g
                JOIN App\Model\Bank\Entity\BankAccount b WITH b.id = g.bankAccount.id
                WHERE g.automaticPairingEnabled = :enabled AND g.state = :state AND b.transactionSource = :source')
            ->setParameter('enabled', true)
            ->setParameter('state', Group::STATE_OPEN)
            ->setParameter('source', BankTransactionSource::FIO->value)
            ->getSingleScalarResult();
    }
}
