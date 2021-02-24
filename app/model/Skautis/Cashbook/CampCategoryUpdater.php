<?php

declare(strict_types=1);

namespace Model\Skautis\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Repositories\ICampCategoryRepository;
use Model\Cashbook\Repositories\ICampRepository;
use Model\Cashbook\Services\ICampCategoryUpdater;
use Model\Event\SkautisCampId;
use Model\Skautis\Exception\AmountMustBeGreaterThanZero;
use Model\Utils\MoneyFactory;
use Skautis\Skautis;
use Skautis\Wsdl\WsdlException;

use function array_diff;
use function array_fill_keys;
use function array_filter;
use function array_keys;
use function count;
use function preg_match;

use const ARRAY_FILTER_USE_BOTH;

final class CampCategoryUpdater implements ICampCategoryUpdater
{
    private Skautis $skautis;

    private ICampRepository $campRepository;

    private ICampCategoryRepository $campCategories;

    public function __construct(
        Skautis $skautis,
        ICampRepository $campRepository,
        ICampCategoryRepository $campCategories
    ) {
        $this->skautis        = $skautis;
        $this->campRepository = $campRepository;
        $this->campCategories = $campCategories;
    }

    /**
     * @param array<int, float> $cashbookTotals
     */
    public function updateCategories(CashbookId $cashbookId, array $cashbookTotals): void
    {
        $campSkautisId = $this->campRepository->findByCashbookId($cashbookId)->getSkautisId();
        $skautisTotals = $this->getSkautisTotals($campSkautisId);

        // Update categories that are not in cashbook, has total > 0 in Skautis
        $categoriesOnlyInSkautis = array_diff(array_keys($skautisTotals), array_keys($cashbookTotals));
        $categoriesOnlyInSkautis = array_filter($categoriesOnlyInSkautis, function (float $total) {
            return $total === 0.0;
        });

        // Update categories that have different total in cashbook and Skautis
        $cashbookTotals = array_filter($cashbookTotals, function (float $total, int $categoryId) use ($skautisTotals) {
            return isset($skautisTotals[$categoryId]) && $skautisTotals[$categoryId] !== $total;
        }, ARRAY_FILTER_USE_BOTH);

        $cashbookTotals += array_fill_keys($categoriesOnlyInSkautis, 0);

        if (count($cashbookTotals) === 0) {
            return;
        }

        try {
            foreach ($cashbookTotals as $categoryId => $total) {
                $this->skautis->event->EventCampStatementUpdate([
                    'ID' => $categoryId,
                    'ID_EventCamp' => $campSkautisId->toInt(),
                    'Ammount' => $total,
                    'IsEstimate' => false,
                ], 'eventCampStatement');
            }
        } catch (WsdlException $exc) {
            if (! preg_match('/Chyba validace \(EventCampStatement_AmmountMustBeGreatherThanZero\)/', $exc->getMessage())) {
                throw $exc;
            }

            throw new AmountMustBeGreaterThanZero();
        }
    }

    /**
     * @return float[]
     */
    private function getSkautisTotals(SkautisCampId $campSkautisId): array
    {
        $categories = $this->campCategories->findForCamp($campSkautisId->toInt());
        $totals     = [];

        foreach ($categories as $category) {
            $totals[$category->getId()] = MoneyFactory::toFloat($category->getTotal());
        }

        return $totals;
    }
}
