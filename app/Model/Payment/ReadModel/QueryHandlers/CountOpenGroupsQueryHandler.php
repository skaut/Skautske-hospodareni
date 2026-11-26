<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\Payment\Group;
use App\Model\Payment\ReadModel\Queries\CountOpenGroupsQuery;
use Doctrine\ORM\EntityManager;

final class CountOpenGroupsQueryHandler
{
    public function __construct(private EntityManager $entityManager)
    {
    }

    public function __invoke(CountOpenGroupsQuery $query): int
    {
        return (int) $this->entityManager
            ->createQuery(/* @lang DQL */ 'SELECT COUNT(g) FROM App\Model\Payment\Group g WHERE g.state = :state')
            ->setParameter('state', Group::STATE_OPEN)
            ->getSingleScalarResult();
    }
}
