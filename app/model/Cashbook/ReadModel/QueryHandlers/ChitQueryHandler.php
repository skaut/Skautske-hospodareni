<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\ChitQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Common\ShouldNotHappenException;
use Model\DTO\Cashbook\Category;
use Model\DTO\Cashbook\Chit;
use Model\DTO\Cashbook\ChitFactory;

final class ChitQueryHandler
{

    /** @var ICashbookRepository */
    private $cashbooks;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(ICashbookRepository $cashbooks, QueryBus $queryBus)
    {
        $this->cashbooks = $cashbooks;
        $this->queryBus = $queryBus;
    }

    public function handle(ChitQuery $query): ?Chit
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());

        foreach ($cashbook->getChits() as $chit) {
            if ($chit->getId() === $query->getChitId()) {
                return ChitFactory::create($chit, $this->getCategory($query->getCashbookId(), $chit->getCategoryId()));
            }
        }

        return NULL;
    }

    private function getCategory(CashbookId $cashbookId, int $categoryId): Category
    {
        /** @var Category[] $categories */
        $categories = $this->queryBus->handle(new CategoryListQuery($cashbookId));

        foreach ($categories as $category) {
            if ($category->getId() === $categoryId) {
                return $category;
            }
        }

        throw new ShouldNotHappenException('Category not found');
    }

}
