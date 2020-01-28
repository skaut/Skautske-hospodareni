<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel;

use Model\Cashbook\CampCategory;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\ICategory;
use Model\Cashbook\MissingCategory;
use Model\Cashbook\ParticipantType;
use function array_key_exists;
use function sprintf;

final class CategoryTotalsCalculator
{
    /**
     * @param ICategory[] $categories
     *
     * @return array<int, float>
     */
    public function calculate(Cashbook $cashbook, array $categories) : array
    {
        $totalByCategories = $cashbook->getCategoryTotals();

        if ($cashbook->getType()->equalsValue(CashbookType::CAMP)) {
            $totalByCategories = self::categorySubtract($totalByCategories, self::getCampIncomeCategoryId($categories, ParticipantType::CHILD()), ICategory::CATEGORY_REFUND_CHILD_ID);
            $totalByCategories = self::categorySubtract($totalByCategories, self::getCampIncomeCategoryId($categories, ParticipantType::ADULT()), ICategory::CATEGORY_REFUND_ADULT_ID);
        } else {
            if (array_key_exists(ICategory::CATEGORY_HPD_ID, $totalByCategories)) {
                $totalByCategories[ICategory::CATEGORY_PARTICIPANT_INCOME_ID] = ($totalByCategories[ICategory::CATEGORY_PARTICIPANT_INCOME_ID] ?? 0) + $totalByCategories[ICategory::CATEGORY_HPD_ID];
                unset($totalByCategories[ICategory::CATEGORY_HPD_ID]);
            }
            $totalByCategories = self::categorySubtract($totalByCategories, ICategory::CATEGORY_PARTICIPANT_INCOME_ID, ICategory::CATEGORY_REFUND_ID);
        }

        return $totalByCategories;
    }

    /**
     * @param array<int, float> $totalByCategories
     *
     * @return array<int, float>
     */
    private static function categorySubtract(array $totalByCategories, int $categoryId, int $temporaryId) : array
    {
        if (array_key_exists($temporaryId, $totalByCategories)) {
            $totalByCategories[$categoryId] = ($totalByCategories[$categoryId] ?? 0) - $totalByCategories[$temporaryId];
            unset($totalByCategories[$temporaryId]);
        }

        return $totalByCategories;
    }

    /**
     * @param ICategory[] $categories
     */
    private static function getCampIncomeCategoryId(array $categories, ParticipantType $type) : ?int
    {
        foreach ($categories as $c) {
            if ($c instanceof CampCategory && $c->getParticipantType() !== null && $c->getParticipantType()->equals($type)) {
                return $c->getId();
            }
        }
        throw new MissingCategory(sprintf('Seznam táborových kategorií neobsahuje požadový typ "%s".', $type->getValue()));
    }
}
