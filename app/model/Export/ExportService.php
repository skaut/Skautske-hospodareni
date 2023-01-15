<?php

declare(strict_types=1);

namespace Model;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ICategory;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use Model\Cashbook\ReadModel\Queries\CampParticipantStatisticsQuery;
use Model\Cashbook\ReadModel\Queries\CashbookDisplayNameQuery;
use Model\Cashbook\ReadModel\Queries\CashbookOfficialUnitQuery;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\EducationCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\EducationParticipantListQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use Model\Cashbook\ReadModel\Queries\EventParticipantStatisticsQuery;
use Model\Cashbook\ReadModel\Queries\FinalRealBalanceQuery;
use Model\Common\Services\QueryBus;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Cashbook\CategorySummary;
use Model\DTO\Cashbook\Chit;
use Model\DTO\Participant\Statistics;
use Model\Event\Camp;
use Model\Event\Education;
use Model\Event\Event;
use Model\Event\ReadModel\Queries\CampFunctions;
use Model\Event\ReadModel\Queries\CampQuery;
use Model\Event\ReadModel\Queries\EducationFunctions;
use Model\Event\ReadModel\Queries\EducationQuery;
use Model\Event\ReadModel\Queries\EventFunctions;
use Model\Event\ReadModel\Queries\EventQuery;
use Model\Event\Repositories\IEventRepository;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEducationId;
use Model\Event\SkautisEventId;
use Model\Participant\Payment\EventType;
use Model\Services\TemplateFactory;
use Model\Utils\MoneyFactory;

use function array_column;
use function array_filter;
use function array_sum;
use function array_values;
use function assert;
use function in_array;
use function is_float;
use function sprintf;

class ExportService
{
    public const CATEGORY_VIRTUAL = 'virtual';
    public const CATEGORY_REAL    = 'real';

    public function __construct(
        private UnitService $units,
        private TemplateFactory $templateFactory,
        private IEventRepository $events,
        private QueryBus $queryBus,
    ) {
    }

    public function getNewPage(): string
    {
        return '<pagebreak type="NEXT-ODD" resetpagenum="1" pagenumstyle="i" suppress="off" />';
    }

    public function getParticipants(int $aid, string $type = EventType::GENERAL): string
    {
        if ($type === EventType::CAMP) {
            $templateFile = __DIR__ . '/templates/participantCamp.latte';
            $camp         = $this->queryBus->handle(new CampQuery(new SkautisCampId($aid)));
            assert($camp instanceof Camp);
            $displayName = $camp->getDisplayName();
            $unitId      = $camp->getUnitId();
            $list        = $this->queryBus->handle(new CampParticipantListQuery($camp->getId()));
        } elseif ($type === EventType::EDUCATION) {
            $templateFile = __DIR__ . '/templates/participantEducation.latte';
            $education    = $this->queryBus->handle(new EducationQuery(new SkautisEducationId($aid)));
            assert($education instanceof Education);
            $displayName = $education->getDisplayName();
            $unitId      = $education->getUnitId();
            $list        = $this->queryBus->handle(new EducationParticipantListQuery($education->getId()));
        } else {
            $templateFile = __DIR__ . '/templates/participant.latte';
            $event        = $this->queryBus->handle(new EventQuery(new SkautisEventId($aid)));
            assert($event instanceof Event);
            $displayName = $event->getDisplayName();
            $unitId      = $event->getUnitId();
            $list        = $this->queryBus->handle(new EventParticipantListQuery($event->getId()));
        }

        return $this->templateFactory->create($templateFile, [
            'list' => $list,
            'displayName' => $displayName,
            'unitFullNameWithAddress' => $this->units->getOfficialUnit($unitId->toInt())->getFullDisplayNameWithAddress(),
        ]);
    }

    /**
     * vrací pokladní knihu
     */
    public function getCashbook(CashbookId $cashbookId, PaymentMethod $paymentMethod): string
    {
        $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));
        assert($cashbook instanceof Cashbook);

        $header = sprintf(
            '%s - %s',
            $paymentMethod->equals(PaymentMethod::CASH()) ? 'Pokladní kniha' : 'Bankovní transakce',
            $this->queryBus->handle(new CashbookDisplayNameQuery($cashbookId)),
        );

        return $this->templateFactory->create(__DIR__ . '/templates/cashbook.latte', [
            'header'  => $header,
            'prefix'  => $cashbook->getChitNumberPrefix($paymentMethod),
            'chits'   => $this->queryBus->handle(ChitListQuery::withMethod($paymentMethod, $cashbookId)),
            'unit'    => $this->queryBus->handle(new CashbookOfficialUnitQuery($cashbookId)),
        ]);
    }

    /**
     * vrací seznam dokladů
     */
    public function getChitlist(CashbookId $cashbookId): string
    {
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $cashbookId));

        return $this->templateFactory->create(__DIR__ . '/templates/chitlist.latte', [
            'list' => array_filter($chits, function (Chit $chit): bool {
                return ! $chit->isIncome();
            }),
        ]);
    }

    public function getEventReport(int $skautisEventId): string
    {
        $sums = [
            self::CATEGORY_VIRTUAL => [
                Operation::INCOME => [],
                Operation::EXPENSE => [],
            ],
            self::CATEGORY_REAL => [
                Operation::INCOME => [],
                Operation::EXPENSE => [],
            ],
        ];

        $cashbookId = $this->queryBus->handle(new EventCashbookIdQuery(new SkautisEventId($skautisEventId)));
        /** @var CategorySummary[] $categoriesSummary */
        $categoriesSummary = $this->queryBus->handle(new CategoriesSummaryQuery($cashbookId));

        foreach ($categoriesSummary as $categorySummary) {
            if (in_array($categorySummary->getId(), [ICategory::CATEGORY_HPD_ID, ICategory::CATEGORY_REFUND_ID], true)) {
                continue;
            }

            $virtual   = $categorySummary->isVirtual() ? self::CATEGORY_VIRTUAL : self::CATEGORY_REAL;
            $operation = $categorySummary->getOperationType()->getValue();

            $sums[$virtual][$operation][$categorySummary->getId()] = [
                'amount' => MoneyFactory::toFloat($categorySummary->getTotal()),
                'label' => $categorySummary->getName(),
            ];
        }

        $totalIncome = array_sum(
            array_column($sums[self::CATEGORY_REAL][Operation::INCOME], 'amount'),
        );

        $totalExpense = array_sum(
            array_column($sums[self::CATEGORY_REAL][Operation::EXPENSE], 'amount'),
        );

        $virtualTotalIncome = array_sum(
            array_column($sums[self::CATEGORY_VIRTUAL][Operation::INCOME], 'amount'),
        );

        $virtualTotalExpense = array_sum(
            array_column($sums[self::CATEGORY_VIRTUAL][Operation::EXPENSE], 'amount'),
        );

        $stats = $this->queryBus->handle(new EventParticipantStatisticsQuery(new SkautisEventId($skautisEventId)));
        assert($stats instanceof Statistics);
        $events    = $this->events->find(new SkautisEventId($skautisEventId));
        $functions = $this->queryBus->handle(new EventFunctions(new SkautisEventId($skautisEventId)));

        return $this->templateFactory->create(__DIR__ . '/templates/eventReport.latte', [
            'participantsCnt' => $stats->getPersonsCount(),
            'personsDays' => $stats->getPersonDays(),
            'event' => $events,
            'chits' => $sums,
            'functions' => $functions,
            'incomes' => array_values($sums[self::CATEGORY_REAL][Operation::INCOME]),
            'expenses' => array_values($sums[self::CATEGORY_REAL][Operation::EXPENSE]),
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'virtualIncomes' => array_values($sums[self::CATEGORY_VIRTUAL][Operation::INCOME]),
            'virtualExpenses' => array_values($sums[self::CATEGORY_VIRTUAL][Operation::EXPENSE]),
            'virtualTotalIncome' => $virtualTotalIncome,
            'virtualTotalExpense' => $virtualTotalExpense,
        ]);
    }

    public function getCampReport(int $skautisCampId, bool $areTotalsConsistentWithSkautis): string
    {
        $cashbookId = $this->queryBus->handle(new CampCashbookIdQuery(new SkautisCampId($skautisCampId)));
        $categories = $this->queryBus->handle(new CategoriesSummaryQuery($cashbookId));

        $total = [
            'income'  => MoneyFactory::zero(),
            'expense' => MoneyFactory::zero(),
            'virtualIncome'  => MoneyFactory::zero(),
            'virtualExpense' => MoneyFactory::zero(),
        ];

        $incomeCategories  = [self::CATEGORY_REAL => [], self::CATEGORY_VIRTUAL => []];
        $expenseCategories = [self::CATEGORY_REAL => [], self::CATEGORY_VIRTUAL => []];

        foreach ($categories as $category) {
            assert($category instanceof CategorySummary);

            $virtualCategory = $category->isVirtual() ? self::CATEGORY_VIRTUAL : self::CATEGORY_REAL;

            if ($category->isIncome()) {
                $key                                  = $category->isVirtual() ? 'virtualIncome' : 'income';
                $total[$key]                          = $total[$key]->add($category->getTotal());
                $incomeCategories[$virtualCategory][] = $category;
            } else {
                $key                                   = $category->isVirtual() ? 'virtualExpense' : 'expense';
                $total[$key]                           = $total[$key]->add($category->getTotal());
                $expenseCategories[$virtualCategory][] = $category;
            }
        }

        $stats = $this->queryBus->handle(new CampParticipantStatisticsQuery(new SkautisCampId($skautisCampId)));
        assert($stats instanceof Statistics);

        $finalRealBalance = MoneyFactory::toFloat($this->queryBus->handle(new FinalRealBalanceQuery($cashbookId)));
        assert(is_float($finalRealBalance));

        return $this->templateFactory->create(__DIR__ . '/templates/campReport.latte', [
            'participantsCnt' => $stats->getPersonsCount(),
            'personsDays' => $stats->getPersonDays(),
            'camp' => $this->queryBus->handle(new CampQuery(new SkautisCampId($skautisCampId))),
            'incomeCategories' => $incomeCategories[self::CATEGORY_REAL],
            'expenseCategories' => $expenseCategories[self::CATEGORY_REAL],
            'totalIncome' => $total['income'],
            'totalExpense' => $total['expense'],
            'virtualIncomeCategories' => $incomeCategories[self::CATEGORY_VIRTUAL],
            'virtualExpenseCategories' => $expenseCategories[self::CATEGORY_VIRTUAL],
            'virtualTotalIncome' => $total['virtualIncome'],
            'virtualTotalExpense' => $total['virtualExpense'],
            'functions' => $this->queryBus->handle(new CampFunctions(new SkautisCampId($skautisCampId))),
            'areTotalsConsistentWithSkautis' => $areTotalsConsistentWithSkautis,
            'finalRealBalance' => $finalRealBalance,
        ]);
    }

    public function getEducationReport(SkautisEducationId $educationId): string
    {
        $cashbookId = $this->queryBus->handle(new EducationCashbookIdQuery($educationId));
        $categories = $this->queryBus->handle(new CategoriesSummaryQuery($cashbookId));

        $total = [
            'income'  => MoneyFactory::zero(),
            'expense' => MoneyFactory::zero(),
            'virtualIncome'  => MoneyFactory::zero(),
            'virtualExpense' => MoneyFactory::zero(),
        ];

        $incomeCategories  = [self::CATEGORY_REAL => [], self::CATEGORY_VIRTUAL => []];
        $expenseCategories = [self::CATEGORY_REAL => [], self::CATEGORY_VIRTUAL => []];

        foreach ($categories as $category) {
            assert($category instanceof CategorySummary);

            $virtualCategory = $category->isVirtual() ? self::CATEGORY_VIRTUAL : self::CATEGORY_REAL;

            if ($category->isIncome()) {
                $key                                  = $category->isVirtual() ? 'virtualIncome' : 'income';
                $total[$key]                          = $total[$key]->add($category->getTotal());
                $incomeCategories[$virtualCategory][] = $category;
            } else {
                $key                                   = $category->isVirtual() ? 'virtualExpense' : 'expense';
                $total[$key]                           = $total[$key]->add($category->getTotal());
                $expenseCategories[$virtualCategory][] = $category;
            }
        }

        $finalRealBalance = MoneyFactory::toFloat($this->queryBus->handle(new FinalRealBalanceQuery($cashbookId)));
        assert(is_float($finalRealBalance));

        return $this->templateFactory->create(__DIR__ . '/templates/educationReport.latte', [
            'education' => $this->queryBus->handle(new EducationQuery($educationId)),
            'incomeCategories' => $incomeCategories[self::CATEGORY_REAL],
            'expenseCategories' => $expenseCategories[self::CATEGORY_REAL],
            'totalIncome' => $total['income'],
            'totalExpense' => $total['expense'],
            'virtualIncomeCategories' => $incomeCategories[self::CATEGORY_VIRTUAL],
            'virtualExpenseCategories' => $expenseCategories[self::CATEGORY_VIRTUAL],
            'virtualTotalIncome' => $total['virtualIncome'],
            'virtualTotalExpense' => $total['virtualExpense'],
            'functions' => $this->queryBus->handle(new EducationFunctions($educationId)),
            'finalRealBalance' => $finalRealBalance,
        ]);
    }
}
