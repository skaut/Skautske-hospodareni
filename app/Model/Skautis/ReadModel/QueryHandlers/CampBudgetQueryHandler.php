<?php

declare(strict_types=1);

namespace App\Model\Skautis\ReadModel\QueryHandlers;

use App\Model\Cashbook\CategoryCatalog;
use App\Model\Cashbook\Operation;
use App\Model\DTO\Skautis\BudgetEntry;
use App\Model\Skautis\ReadModel\Queries\CampBudgetQuery;
use App\Model\Utils\MoneyFactory;
use Skautis\Wsdl\WebServiceInterface;

use function array_column;
use function usort;

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

        $entries = [];
        foreach ($skautisCategories as $index => $category) {
            $operation = Operation::get($category->IsRevenue ? Operation::INCOME : Operation::EXPENSE);
            $entries[] = [
                'entry' => new BudgetEntry(
                    $category->EventCampStatementType,
                    MoneyFactory::fromFloat((float) $category->Ammount),
                    $category->IsRevenue,
                ),
                'position' => CategoryCatalog::budgetPosition($category->EventCampStatementType, $operation),
                'sourceIndex' => $index,
            ];
        }

        usort($entries, static function (array $left, array $right): int {
            $leftPosition = $left['position'] ?? PHP_INT_MAX;
            $rightPosition = $right['position'] ?? PHP_INT_MAX;

            return $leftPosition === $rightPosition
                ? $left['sourceIndex'] <=> $right['sourceIndex']
                : $leftPosition <=> $rightPosition;
        });

        return array_column($entries, 'entry');
    }
}
