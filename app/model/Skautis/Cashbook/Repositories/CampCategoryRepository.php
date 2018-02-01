<?php

declare(strict_types=1);

namespace Model\Skautis\Cashbook\Repositories;

use Model\Cashbook\ICategory;
use Model\Cashbook\Operation;
use Model\Cashbook\Repositories\ICampCategoryRepository;
use Model\Skautis\DTO\CampCategory;
use Skautis\Wsdl\WebServiceInterface;

final class CampCategoryRepository implements ICampCategoryRepository
{

    /** @var WebServiceInterface */
    private $eventWebService;

    public function __construct(WebServiceInterface $eventWebService)
    {
        $this->eventWebService = $eventWebService;
    }

    /**
     * @return ICategory[]
     */
    public function findForCamp(int $campId): array
    {
        $categories = [
            new CampCategory(ICategory::UNDEFINED_EXPENSE_ID, Operation::get(Operation::EXPENSE), 'Neurčeno'),
            new CampCategory(ICategory::UNDEFINED_INCOME_ID, Operation::get(Operation::INCOME), 'Neurčeno'),
        ];

        $skautisCategories = $this->eventWebService->EventCampStatementAll([
            'ID_EventCamp' => $campId,
            'IsEstimate' => FALSE,
        ]);

        foreach ($skautisCategories as $category) {
            if ($category->ID_EventCampStatementType === ICategory::CAMP_RESERVE_ID) {
                continue;
            }

            $operation = Operation::get($category->IsRevenue ? Operation::INCOME : Operation::EXPENSE);

            $categories[] = new CampCategory($category->ID, $operation, $category->EventCampStatementType);
        }

        return $categories;
    }

}
