<?php

declare(strict_types=1);

namespace App\Model\Skautis\Cashbook\Repositories;

use App\Model\Cashbook\CampCategory;
use App\Model\Cashbook\ICategory;
use App\Model\Cashbook\Operation;
use App\Model\Cashbook\ParticipantType;
use App\Model\Cashbook\Repositories\ICampCategoryRepository;
use App\Model\Utils\MoneyFactory;
use LogicException;
use Skautis\Wsdl\WebServiceInterface;
use stdClass;

use function is_object;

final class CampCategoryRepository implements ICampCategoryRepository
{
    private const PARTICIPANT_CATEGORIES = [
        1 => ParticipantType::CHILD,
        3 => ParticipantType::ADULT,
    ];

    public function __construct(private WebServiceInterface $eventWebService)
    {
    }

    /** @return CampCategory[] */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function findForCamp(int $campId): array
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
            if (! $category instanceof stdClass) {
                throw new LogicException('Assertion failed.');
            }
            if ($category->ID_EventCampStatementType === ICategory::CAMP_RESERVE_ID) {
                continue;
            }

            $operation = Operation::get($category->IsRevenue ? Operation::INCOME : Operation::EXPENSE);

            $categories[] = new CampCategory(
                $category->ID,
                $operation,
                $category->EventCampStatementType,
                MoneyFactory::fromFloat((float) $category->Ammount),
                $this->getParticipantType($category),
            );
        }

        return $categories;
    }

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    private function getParticipantType(stdClass $category): ?ParticipantType
    {
        $categoryId = $category->ID_EventCampStatementType ?? null;

        if ($categoryId === null || ! isset(self::PARTICIPANT_CATEGORIES[$categoryId])) {
            return null;
        }

        return ParticipantType::get(self::PARTICIPANT_CATEGORIES[$categoryId]);
    }
}
