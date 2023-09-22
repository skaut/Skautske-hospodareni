<?php

declare(strict_types=1);

namespace Model\Skautis\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Repositories\IEducationCategoryRepository;
use Model\Cashbook\Repositories\IEducationRepository;
use Model\Cashbook\Services\IEducationCategoryUpdater;
use Model\Event\SkautisEducationId;
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

final class EducationCategoryUpdater implements IEducationCategoryUpdater
{
    public function __construct(
        private Skautis $skautis,
        private IEducationRepository $educationRepository,
        private IEducationCategoryRepository $educationCategories,
    ) {
    }

    /** @param array<int, float> $cashbookTotals */
    public function updateCategories(CashbookId $cashbookId, array $cashbookTotals): void
    {
        $educationSkautisId = $this->educationRepository->findByCashbookId($cashbookId)->getSkautisId();
        $skautisTotals      = $this->getSkautisTotals($educationSkautisId);

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
                $this->skautis->Grants->StatementUpdate([
                    'ID' => $categoryId,
                    'ID_EventEducation' => $educationSkautisId->toInt(),
                    'Ammount' => -300,
                    'IsBudget' => false,
                ], 'statement');
            }
        } catch (WsdlException $exc) {
            if (! preg_match('/Chyba validace \(EventStatement_EnterAmmountGreatherThanZero\)/', $exc->getMessage())) {
                throw $exc;
            }

            throw new AmountMustBeGreaterThanZero();
        }
    }

    /** @return float[] */
    private function getSkautisTotals(SkautisEducationId $educationSkautisId): array
    {
        $categories = $this->educationCategories->findForEducation($educationSkautisId->toInt());
        $totals     = [];

        foreach ($categories as $category) {
            $totals[$category->getId()] = MoneyFactory::toFloat($category->getTotal());
        }

        return $totals;
    }
}
