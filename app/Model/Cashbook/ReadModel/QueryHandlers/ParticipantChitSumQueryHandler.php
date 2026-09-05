<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\Cashbook\Chit;
use App\Model\Cashbook\ReadModel\Queries\ParticipantChitSumQuery;
use App\Model\Utils\MoneyFactory;
use Doctrine\ORM\EntityManager;
use Money\Money;


class ParticipantChitSumQueryHandler
{
    private const PARTICIPANT_INCOME_CATEGORY_IDS = [1, 11];

    public function __construct(private EntityManager $entityManager)
    {
    }

    public function __invoke(ParticipantChitSumQuery $query): Money
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

        return array_reduce($chits, fn (Money $total, Chit $chit): Money => $total->add($chit->getAmount()->toMoney()), MoneyFactory::zero());
    }
}
