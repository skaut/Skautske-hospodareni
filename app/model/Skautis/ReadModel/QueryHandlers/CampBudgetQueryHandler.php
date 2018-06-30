<?php

declare(strict_types=1);

namespace Model\Skautis\ReadModel\QueryHandlers;

use Model\DTO\Skautis\BudgetEntry;
use Model\Skautis\ReadModel\Queries\CampBudgetQuery;
use Model\Utils\MoneyFactory;
use Skautis\Wsdl\WebServiceInterface;

final class CampBudgetQueryHandler
{

    /** @var WebServiceInterface */
    private $eventWebService;

    public function __construct(WebServiceInterface $eventWebService)
    {
        $this->eventWebService = $eventWebService;
    }

    /**
     * @return BudgetEntry[]
     */
    public function handle(CampBudgetQuery $query): array
    {
        $skautisCategories = $this->eventWebService->EventCampStatementAll([
            'ID_EventCamp' => $query->getCampId()->toInt(),
            'IsEstimate' => TRUE,
        ]);

        return array_map(function (\stdClass $category): BudgetEntry {
            return new BudgetEntry(
                $category->EventCampStatementType,
                MoneyFactory::fromFloat((float)$category->Ammount),
                $category->IsRevenue
            );
        }, $skautisCategories);
    }

}
