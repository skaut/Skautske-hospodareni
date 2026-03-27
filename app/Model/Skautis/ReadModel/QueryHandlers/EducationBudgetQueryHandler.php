<?php

declare(strict_types=1);

namespace App\Model\Skautis\ReadModel\QueryHandlers;

use App\Model\DTO\Skautis\BudgetEntry;
use App\Model\Skautis\ReadModel\Queries\EducationBudgetQuery;
use App\Model\Utils\MoneyFactory;
use Skautis\Wsdl\WebServiceInterface;
use stdClass;

use function array_map;

final class EducationBudgetQueryHandler
{
    public function __construct(private WebServiceInterface $grantWebService)
    {
    }

    /** @return BudgetEntry[] */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function __invoke(EducationBudgetQuery $query): array
    {
        $skautisCategories = $this->grantWebService->StatementAll([
            'ID_EventEducation' => $query->getEducationId()->toInt(),
            'ID_Grant' => $query->getGrantId()->toInt(),
            'IsBudget' => true,
        ]);

        return array_map(function (stdClass $category): BudgetEntry {
            return new BudgetEntry(
                $category->StatementType,
                MoneyFactory::fromFloat((float) $category->Ammount),
                $category->IsRevenue,
            );
        }, $skautisCategories);
    }
}
