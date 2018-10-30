<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\Chit;
use Model\Cashbook\Category;
use Model\Cashbook\ReadModel\Queries\ParticipantChitSumQuery;
use function array_map;
use function array_sum;

class ParticipantChitSumQueryHandler
{
    public const PARTICIPANT_INCOME_CATEGORY_SHORTCUTS = [1, 11];
    /** @var EntityManager */
    private $entityManager;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(EntityManager $entityManager, QueryBus $queryBus)
    {
        $this->entityManager = $entityManager;
        $this->queryBus      = $queryBus;
    }

    public function handle(ParticipantChitSumQuery $query) : float
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(Chit::class, 'c')
            ->leftJoin(Category::class, 'cat', Join::WITH, 'cat.id = c.category.id')
            ->where('IDENTITY(c.cashbook) = :cashbookId')
            ->andWhere('cat.id IN (:cat_id)')
            ->setParameter('cashbookId', $query->getCashbookId()->toString())
            ->setParameter('cat_id', self::PARTICIPANT_INCOME_CATEGORY_SHORTCUTS);

        $chits = $queryBuilder->getQuery()->getResult();
        $sum   = array_sum(array_map(function (Chit $c) {
            return $c->getBody()->getAmount()->toFloat();
        }, $chits));

        return $sum;
    }
}
