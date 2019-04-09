<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Doctrine\ORM\EntityManager;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\Chit;
use Model\Cashbook\ReadModel\Queries\ParticipantChitSumQuery;
use function array_map;
use function array_sum;

class ParticipantChitSumQueryHandler
{
    private const PARTICIPANT_INCOME_CATEGORY_IDS = [1, 11];
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
            ->join('c.items', 'ci')
            ->where('IDENTITY(c.cashbook) = :cashbookId')
            ->andWhere('ci.category.id IN (:category_ids)')
            ->setParameter('cashbookId', $query->getCashbookId()->toString())
            ->setParameter('category_ids', self::PARTICIPANT_INCOME_CATEGORY_IDS);

        $chits = $queryBuilder->getQuery()->getResult();
        $sum   = array_sum(array_map(function (Chit $c) {
            return $c->getBody()->getAmount()->toFloat();
        }, $chits));

        return $sum;
    }
}
