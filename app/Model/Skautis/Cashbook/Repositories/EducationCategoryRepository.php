<?php

declare(strict_types=1);

namespace App\Model\Skautis\Cashbook\Repositories;

use App\Model\Cashbook\EducationCategory;
use App\Model\Cashbook\Operation;
use App\Model\Cashbook\Repositories\IEducationCategoryRepository;
use App\Model\Utils\MoneyFactory;
use Skautis\Wsdl\WebServiceInterface;
use stdClass;

use function assert;
use function is_object;

final class EducationCategoryRepository implements IEducationCategoryRepository
{
    public function __construct(private WebServiceInterface $grantsWebService)
    {
    }

    /** @return EducationCategory[] */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function findForEducation(int $educationId, int $year): array
    {
        $skautisCategories = $this->grantsWebService->StatementAll([
            'ID_EventEducation' => $educationId,
            'IsBudget' => false,
            'Year' => $year,
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
                MoneyFactory::fromFloat((float) $category->Ammount),
            );
        }

        return $categories;
    }
}
