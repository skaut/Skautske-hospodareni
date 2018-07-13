<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\Chit;
use Model\Cashbook\CashbookNotFoundException;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\DTO\Cashbook\Category;
use Model\DTO\Cashbook\Chit as ChitDTO;
use Model\DTO\Cashbook\ChitFactory;
use function array_map;
use function usort;

class ChitListQueryHandler
{
    /** @var ICashbookRepository */
    private $cashbooks;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(ICashbookRepository $cashbooks, QueryBus $queryBus)
    {
        $this->cashbooks = $cashbooks;
        $this->queryBus  = $queryBus;
    }

    /**
     * @return ChitDTO[]
     * @throws CashbookNotFoundException
     */
    public function handle(ChitListQuery $query) : array
    {
        $cashbook = $this->cashbooks->find($query->getCashbookId());

        $chits = $cashbook->getChits();

        usort(
            $chits,
            function (Chit $a, Chit $b) : int {
                return [
                $a->getDate(),
                $a->getCategory()->getOperationType()->getValue(),
                $a->getId(),
                ] <=> [
                $b->getDate(),
                $b->getCategory()->getOperationType()->getValue(),
                $b->getId(),
                ];
            }
        );

        $categories = $this->getCategories($query->getCashbookId());

        return array_map(
            function (Chit $chit) use ($categories) : ChitDTO {
                return ChitFactory::create($chit, $categories[$chit->getCategoryId()]);
            },
            $chits
        );
    }

    /**
     * @return array<int, Category>
     */
    private function getCategories(CashbookId $cashbookId) : array
    {
        /**
 * @var Category[] $categories
*/
        $categories     = $this->queryBus->handle(new CategoryListQuery($cashbookId));
        $categoriesById = [];

        foreach ($categories as $category) {
            $categoriesById[$category->getId()] = $category;
        }

        return $categoriesById;
    }
}
