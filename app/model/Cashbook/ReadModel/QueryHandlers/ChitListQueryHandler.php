<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Doctrine\ORM\EntityManager;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\Chit;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\DTO\Cashbook\Category;
use Model\DTO\Cashbook\Chit as ChitDTO;
use Model\DTO\Cashbook\ChitFactory;
use function array_map;

class ChitListQueryHandler
{
    /** @var EntityManager */
    private $entityManager;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(EntityManager $entityManager, QueryBus $queryBus)
    {
        $this->entityManager = $entityManager;
        $this->queryBus      = $queryBus;
    }

    /**
     * @return ChitDTO[]
     * @throws CashbookNotFound
     */
    public function handle(ChitListQuery $query) : array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(Chit::class, 'c')
            ->join('c.items', 'ci')
            ->where('IDENTITY(c.cashbook) = :cashbookId')
            ->setParameter('cashbookId', $query->getCashbookId()->toString())
            ->orderBy('c.body.date')
            ->addOrderBy('ci.category.operationType') // income first
            ->addOrderBy('c.id');

        if ($query->getPaymentMethod() !== null) {
            $queryBuilder->andWhere('c.paymentMethod = :paymentMethod')
                ->setParameter('paymentMethod', $query->getPaymentMethod()->toString());
        }

        $chits = $queryBuilder->getQuery()->getResult();

        /** @var Category[] $categories */
        $categories = $this->queryBus->handle(new CategoryListQuery($query->getCashbookId()));

        return array_map(
            function (Chit $chit) use ($categories) : ChitDTO {
                return ChitFactory::create($chit, $categories);
            },
            $chits
        );
    }
}
