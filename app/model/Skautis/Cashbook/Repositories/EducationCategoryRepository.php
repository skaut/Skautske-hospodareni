<?php

declare(strict_types=1);

namespace Model\Skautis\Cashbook\Repositories;

use Model\Cashbook\EducationCategory;
use Model\Cashbook\Operation;
use Model\Cashbook\Repositories\IEducationCategoryRepository;
use Model\Utils\MoneyFactory;
use Skautis\Wsdl\WebServiceInterface;
use stdClass;

use function assert;
use function is_object;

final class EducationCategoryRepository implements IEducationCategoryRepository
{
    private WebServiceInterface $grantsWebService;

    public function __construct(WebServiceInterface $grantsWebService)
    {
        $this->grantsWebService = $grantsWebService;
    }

    /**
     * @return EducationCategory[]
     */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function findForEducation(int $educationId): array
    {
        $skautisCategories = $this->grantsWebService->StatementAll([
            'ID_EventEducation' => $educationId,
            'IsBudget' => false,
            'Year' => '',
        ]);

        if (is_object($skautisCategories)) {
            return []; // API returns empty object when there are no results
        }

        $categories = [];

        foreach ($skautisCategories as $category) {
            assert($category instanceof stdClass);

            $categories[] = new EducationCategory(
                $category->ID,
                Operation::get($category->IsRevenue ? Operation::INCOME : Operation::EXPENSE),
                $category->StatementType,
                MoneyFactory::fromFloat((float) $category->Ammount)
            );
        }

        return $categories;
    }
}
