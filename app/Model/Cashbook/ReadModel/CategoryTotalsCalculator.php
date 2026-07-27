<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel;

use App\Model\Cashbook\CampCategory;
use App\Model\Cashbook\Cashbook;
use App\Model\Cashbook\Cashbook\CashbookType;
use App\Model\Cashbook\EducationCategory;
use App\Model\Cashbook\ICategory;
use App\Model\Cashbook\MissingCategory;
use App\Model\Cashbook\ParticipantType;

use function array_key_exists;
use function sprintf;

final class CategoryTotalsCalculator
{
    /**
     * @param ICategory[] $categories
     *
     * @return array<int, float>
     */
    public function calculate(Cashbook $cashbook, array $categories): array
    {
        $totalByCategories = $cashbook->getCategoryTotals();

        if ($cashbook->getType()->equalsValue(CashbookType::CAMP)) {
            $totalByCategories = self::categorySubtract($totalByCategories, fn (): int => self::getCampIncomeCategoryId($categories, ParticipantType::CHILD()), ICategory::CATEGORY_REFUND_CHILD_ID);
            $totalByCategories = self::categorySubtract($totalByCategories, fn (): int => self::getCampIncomeCategoryId($categories, ParticipantType::ADULT()), ICategory::CATEGORY_REFUND_ADULT_ID);
        } elseif ($cashbook->getType()->equalsValue(CashbookType::EDUCATION)) {
            $totalByCategories = self::categorySubtract($totalByCategories, fn (): int => self::getEducationIncomeCategoryId($categories), ICategory::CATEGORY_REFUND_ID);
        } else {
            if (array_key_exists(ICategory::CATEGORY_HPD_ID, $totalByCategories)) {
                $totalByCategories[ICategory::CATEGORY_PARTICIPANT_INCOME_ID] = ($totalByCategories[ICategory::CATEGORY_PARTICIPANT_INCOME_ID] ?? 0) + $totalByCategories[ICategory::CATEGORY_HPD_ID];
                unset($totalByCategories[ICategory::CATEGORY_HPD_ID]);
            }

            $totalByCategories = self::categorySubtract($totalByCategories, fn (): int => ICategory::CATEGORY_PARTICIPANT_INCOME_ID, ICategory::CATEGORY_REFUND_ID);
        }

        return $totalByCategories;
    }

    /**
     * Dohledání cílové kategorie (`$resolveCategoryId`) je líné – provede se jen tehdy, když je opravdu co
     * odečítat (existuje dočasná kategorie vratky). Bez vratky se tak nevyhazuje MissingCategory u pokladen,
     * které příslušnou příjmovou kategorii nemají (např. prázdná vzdělávačka / tábor) – stejně jako u akcí.
     *
     * @param array<int, float> $totalByCategories
     * @param callable(): int   $resolveCategoryId
     *
     * @return array<int, float>
     */
    private static function categorySubtract(array $totalByCategories, callable $resolveCategoryId, int $temporaryId): array
    {
        if (array_key_exists($temporaryId, $totalByCategories)) {
            $categoryId = $resolveCategoryId();
            $totalByCategories[$categoryId] = ($totalByCategories[$categoryId] ?? 0) - $totalByCategories[$temporaryId];
            unset($totalByCategories[$temporaryId]);
        }

        return $totalByCategories;
    }

    /** @param ICategory[] $categories */
    private static function getCampIncomeCategoryId(array $categories, ParticipantType $type): int
    {
        foreach ($categories as $c) {
            if ($c instanceof CampCategory && $c->getParticipantType() !== null && $c->getParticipantType()->equals($type)) {
                return $c->getId();
            }
        }

        throw new MissingCategory(sprintf('Seznam táborových kategorií neobsahuje požadový typ "%s".', $type->getValue()));
    }

    /** @param ICategory[] $categories */
    private static function getEducationIncomeCategoryId(array $categories): int
    {
        foreach ($categories as $c) {
            if ($c instanceof EducationCategory && $c->getName() === 'Účastnické poplatky') {
                return $c->getId();
            }
        }

        throw new MissingCategory('Seznam kategorií vzdělávačky neobsahuje účastnické poplatky.');
    }
}
