<?php

declare(strict_types=1);

namespace App\Model\Skautis\ReadModel\QueryHandlers;

use App\Model\DTO\Skautis\BudgetEntry;
use App\Model\Skautis\ReadModel\Queries\CampBudgetQuery;
use App\Model\Utils\MoneyFactory;
use Skautis\Wsdl\WebServiceInterface;
use stdClass;

use function array_map;

final class CampBudgetQueryHandler
{
    public function __construct(private WebServiceInterface $eventWebService)
    {
    }

    /** @return BudgetEntry[] */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function __invoke(CampBudgetQuery $query): array
    {
        $skautisCategories = $this->eventWebService->EventCampStatementAll([
            'ID_EventCamp' => $query->getCampId()->toInt(),
            'IsEstimate' => true,
        ]);

        return array_map(function (stdClass $category): BudgetEntry {
            return new BudgetEntry(
                $category->EventCampStatementType,
                MoneyFactory::fromFloat((float) $category->Ammount),
                $category->IsRevenue,
            );
        }, $skautisCategories);
    }
}
