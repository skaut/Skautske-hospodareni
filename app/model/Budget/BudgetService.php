<?php

declare(strict_types=1);

namespace Model;

use Model\Budget\Repositories\ICategoryRepository;
use Model\Budget\Unit\Category;
use Model\Cashbook\Operation;
use Model\DTO\Budget\CategoryFactory;
use function array_map;
use function assert;
use function str_replace;

class BudgetService
{
    private ICategoryRepository $repository;

    public function __construct(ICategoryRepository $budgetRepository)
    {
        $this->repository = $budgetRepository;
    }

    /**
     * @return mixed[]
     */
    public function getCategories(int $unitId) : array
    {
        return [
            'in' => array_map([CategoryFactory::class, 'create'], $this->repository->findCategories($unitId, Operation::INCOME())),
            'out' => array_map([CategoryFactory::class, 'create'], $this->repository->findCategories($unitId, Operation::EXPENSE())),
        ];
    }

    public function addCategory(int $unitId, string $label, string $type, ?int $parentId, string $value, int $year) : void
    {
        $category = new Category(
            $unitId,
            $label,
            Operation::get($type),
            $parentId === null ? null : $this->repository->find($parentId),
            (float) str_replace(',', '.', $value),
            $year
        );
        $this->repository->save($category);
    }

    /**
     * @return string[]
     */
    public function getCategoriesRoot(int $unitId, string $type) : array
    {
        $res = [];
        foreach ($this->repository->findCategories($unitId, Operation::get($type)) as $category) {
            assert($category instanceof Category);
            $res[$category->getId()] = $category->getLabel();
        }

        return $res;
    }
}
