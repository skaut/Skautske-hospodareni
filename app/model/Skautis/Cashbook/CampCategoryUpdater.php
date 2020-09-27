<?php

declare(strict_types=1);

namespace Model\Skautis\Cashbook;

use InvalidArgumentException;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ObjectType;
use Model\Cashbook\Repositories\ICampCategoryRepository;
use Model\Cashbook\Services\ICampCategoryUpdater;
use Model\Skautis\Exception\AmountMustBeGreaterThanZero;
use Model\Skautis\Mapper;
use Model\Utils\MoneyFactory;
use Skautis\Skautis;
use Skautis\Wsdl\WsdlException;
use const ARRAY_FILTER_USE_BOTH;
use function array_diff;
use function array_fill_keys;
use function array_filter;
use function array_keys;
use function count;
use function preg_match;

final class CampCategoryUpdater implements ICampCategoryUpdater
{
    /** @var Skautis */
    private $skautis;

    /** @var Mapper */
    private $mapper;

    /** @var ICampCategoryRepository */
    private $campCategories;

    public function __construct(Skautis $skautis, Mapper $mapper, ICampCategoryRepository $campCategories)
    {
        $this->skautis        = $skautis;
        $this->mapper         = $mapper;
        $this->campCategories = $campCategories;
    }

    /**
     * @param array<int, float> $cashbookTotals
     */
    public function updateCategories(CashbookId $cashbookId, array $cashbookTotals) : void
    {
        $campSkautisId = $this->mapper->getSkautisId($cashbookId, ObjectType::CAMP);
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

        if ($campSkautisId === null) {
            throw new InvalidArgumentException('Camp #' . $cashbookId . " doesn't exist");
        }

        try {
            foreach ($cashbookTotals as $categoryId => $total) {
                $this->skautis->event->EventCampStatementUpdate([
                    'ID' => $categoryId,
                    'ID_EventCamp' => $campSkautisId,
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
    private function getSkautisTotals(int $campSkautisId) : array
    {
        $categories = $this->campCategories->findForCamp($campSkautisId);
        $totals     = [];

        foreach ($categories as $category) {
            $totals[$category->getId()] = MoneyFactory::toFloat($category->getTotal());
        }

        return $totals;
    }
}
