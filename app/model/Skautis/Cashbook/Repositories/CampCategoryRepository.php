<?php

declare(strict_types=1);

namespace Model\Skautis\Cashbook\Repositories;

use Model\Cashbook\CampCategory;
use Model\Cashbook\ICategory;
use Model\Cashbook\Operation;
use Model\Cashbook\ParticipantType;
use Model\Cashbook\Repositories\ICampCategoryRepository;
use Model\Utils\MoneyFactory;
use Skautis\Wsdl\WebServiceInterface;
use stdClass;
use function is_object;

final class CampCategoryRepository implements ICampCategoryRepository
{
    private const PARTICIPANT_CATEGORIES = [
        1 => ParticipantType::CHILD,
        3 => ParticipantType::ADULT,
    ];

    private WebServiceInterface $eventWebService;

    public function __construct(WebServiceInterface $eventWebService)
    {
        $this->eventWebService = $eventWebService;
    }

    /**
     * @return CampCategory[]
     */
    public function findForCamp(int $campId) : array
    {
        $skautisCategories = $this->eventWebService->EventCampStatementAll([
            'ID_EventCamp' => $campId,
            'IsEstimate' => false,
        ]);

        if (is_object($skautisCategories)) {
            return []; // API returns empty object when there are no results
        }

        $categories = [];

        foreach ($skautisCategories as $category) {
            if ($category->ID_EventCampStatementType === ICategory::CAMP_RESERVE_ID) {
                continue;
            }

            $operation = Operation::get($category->IsRevenue ? Operation::INCOME : Operation::EXPENSE);

            $categories[] = new CampCategory(
                $category->ID,
                $operation,
                $category->EventCampStatementType,
                MoneyFactory::fromFloat((float) $category->Ammount),
                $this->getParticipantType($category)
            );
        }

        return $categories;
    }

    private function getParticipantType(stdClass $category) : ?ParticipantType
    {
        $categoryId = $category->ID_EventCampStatementType ?? null;

        if ($categoryId === null || ! isset(self::PARTICIPANT_CATEGORIES[$categoryId])) {
            return null;
        }

        return ParticipantType::get(self::PARTICIPANT_CATEGORIES[$categoryId]);
    }
}
