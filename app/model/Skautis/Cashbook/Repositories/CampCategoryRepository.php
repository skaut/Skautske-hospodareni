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
use function is_object;

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

    private const MANUAL_CATEGORIES = [
        ['operation' => Operation::EXPENSE, 'id' => ICategory::UNDEFINED_EXPENSE_ID, 'label' => 'Neurčeno'],
        ['operation' => Operation::EXPENSE, 'id' => ICategory::CAMP_TRANSFER_TO_UNIT, 'label' => 'Převod do stř. pokladny'],
        ['operation' => Operation::INCOME, 'id' => ICategory::UNDEFINED_INCOME_ID, 'label' => 'Neurčeno'],
        ['operation' => Operation::INCOME, 'id' => ICategory::CAMP_TRANSFER_FROM_UNIT, 'label' => 'Převod z pokladny střediska'],
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
    public function findForCamp(int $campId) : array
    {
        $categories = [];

        foreach (self::MANUAL_CATEGORIES as $category) {
            $categories[] = new CampCategory(
                (int) $category['id'],
                Operation::get($category['operation']),
                (string) $category['label'],
                MoneyFactory::zero(),
                null
            );
        }

        $skautisCategories = $this->eventWebService->EventCampStatementAll([
            'ID_EventCamp' => $campId,
            'IsEstimate' => false,
        ]);

        if (is_object($skautisCategories)) { // API returns empty object when there are no results
            return [];
        }

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

    private function getParticipantType(\stdClass $category) : ?ParticipantType
    {
        $categoryId = $category->ID_EventCampStatementType ?? null;

        if ($categoryId === null || ! isset(self::PARTICIPANT_CATEGORIES[$categoryId])) {
            return null;
        }

        return ParticipantType::get(self::PARTICIPANT_CATEGORIES[$categoryId]);
    }
}
