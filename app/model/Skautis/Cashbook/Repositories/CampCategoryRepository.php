<?php

declare(strict_types=1);

namespace Model\Skautis\Cashbook\Repositories;

use Model\Cashbook\ICategory;
use Model\Cashbook\Operation;
use Model\Cashbook\ParticipantType;
use Model\Cashbook\Repositories\ICampCategoryRepository;
use Model\Cashbook\CampCategory;
use Model\Utils\MoneyFactory;
use Skautis\Wsdl\WebServiceInterface;

final class CampCategoryRepository implements ICampCategoryRepository
{

    private const PARTICIPANT_CATEGORIES = [
        1 => ParticipantType::CHILD,
        3 => ParticipantType::ADULT,
    ];

    private const UNDEFINED_CATEGORIES = [
        Operation::EXPENSE => ICategory::UNDEFINED_EXPENSE_ID,
        Operation::INCOME => ICategory::UNDEFINED_INCOME_ID,
    ];

    /** @var WebServiceInterface */
    private $eventWebService;

    public function __construct(WebServiceInterface $eventWebService)
    {
        $this->eventWebService = $eventWebService;
    }

    /**
     * @return CampCategory[]
     */
    public function findForCamp(int $campId): array
    {
        $categories = [];

        foreach (self::UNDEFINED_CATEGORIES as $operation => $categoryId) {
            $categories[] = new CampCategory(
                $categoryId,
                Operation::get($operation),
                'Neurčeno',
                MoneyFactory::zero(),
                NULL
            );
        }

        $skautisCategories = $this->eventWebService->EventCampStatementAll([
            'ID_EventCamp' => $campId,
            'IsEstimate' => FALSE,
        ]);

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

    private function getParticipantType(\stdClass $category): ?ParticipantType
    {
        $categoryId = $category->ID_EventCampStatementType ?? NULL;

        if ($categoryId === NULL || ! isset(self::PARTICIPANT_CATEGORIES[$categoryId])) {
            return NULL;
        }

        return ParticipantType::get(self::PARTICIPANT_CATEGORIES[$categoryId]);
    }

}
