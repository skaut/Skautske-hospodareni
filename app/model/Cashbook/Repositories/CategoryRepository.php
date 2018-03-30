<?php

declare(strict_types=1);

namespace Model\Cashbook\Repositories;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\CashbookService;
use Model\Cashbook\CategoryNotFoundException;
use Model\Cashbook\ICategory;

class CategoryRepository
{

    /** @var ICampCategoryRepository */
    private $campCategories;

    /** @var IStaticCategoryRepository */
    private $staticCategories;

    /** @var CashbookService */
    private $cashbookService;

    public function __construct(ICampCategoryRepository $campCategories, IStaticCategoryRepository $staticCategories, CashbookService $cashbookService)
    {
        $this->campCategories = $campCategories;
        $this->staticCategories = $staticCategories;
        $this->cashbookService = $cashbookService;
    }


    /**
     * @return ICategory[]
     */
    public function findForCashbook(CashbookId $cashbookId, CashbookType $type): array
    {
        $skautisType = $type->getSkautisObjectType();

        if ($skautisType->equalsValue(CashbookType::CAMP)) {
            $campId = $this->cashbookService->getSkautisIdFromCashbookId($cashbookId, $skautisType);

            return $this->campCategories->findForCamp($campId);
        }

        return $this->staticCategories->findByObjectType($skautisType);
    }

    /**
     * @throws CategoryNotFoundException
     */
    public function find(int $categoryId, CashbookId $cashbookId, CashbookType $type): ICategory
    {
        if ($type->equalsValue(CashbookType::CAMP)) {
            foreach ($this->findForCashbook($cashbookId, $type) as $category) {
                if ($category->getId() === $categoryId) {
                    return $category;
                }
            }

            throw new CategoryNotFoundException("Category #$categoryId for cashbook #$cashbookId not found");
        }

        $category = $this->staticCategories->find($categoryId);

        if ( ! $category->supportsType($type->getSkautisObjectType())) {
            throw new CategoryNotFoundException(
                sprintf("Category #%d found, but it doesn't support cashbook type %s", $categoryId, $type->getValue())
            );
        }

        return $category;
    }

}
