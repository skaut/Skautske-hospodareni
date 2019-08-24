<?php

declare(strict_types=1);

namespace Model;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ICategory;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\CashbookOfficialUnitQuery;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Cashbook\Repositories\IStaticCategoryRepository;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Cashbook\Category;
use Model\DTO\Cashbook\Chit;
use Model\Event\Camp;
use Model\Event\Event;
use Model\Event\ReadModel\Queries\CampFunctions;
use Model\Event\ReadModel\Queries\CampQuery;
use Model\Event\ReadModel\Queries\EventFunctions;
use Model\Event\ReadModel\Queries\EventQuery;
use Model\Event\Repositories\IEventRepository;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;
use Model\Services\TemplateFactory;
use Model\Utils\MoneyFactory;
use function array_column;
use function array_filter;
use function array_sum;
use function array_values;
use function assert;
use function count;
use function in_array;

class ExportService
{
    public const CATEGORY_VIRTUAL = 'virtual';
    public const CATEGORY_REAL    = 'real';

    /** @var UnitService */
    private $units;

    /** @var IStaticCategoryRepository */
    private $categories;

    /** @var TemplateFactory */
    private $templateFactory;

    /** @var IEventRepository */
    private $events;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(
        UnitService $units,
        IStaticCategoryRepository $categories,
        TemplateFactory $templateFactory,
        IEventRepository $events,
        QueryBus $queryBus
    ) {
        $this->units           = $units;
        $this->categories      = $categories;
        $this->templateFactory = $templateFactory;
        $this->events          = $events;
        $this->queryBus        = $queryBus;
    }

    public function getNewPage() : string
    {
        return '<pagebreak type="NEXT-ODD" resetpagenum="1" pagenumstyle="i" suppress="off" />';
    }

    public function getParticipants(int $aid, EventEntity $service, string $type = 'general') : string
    {
        if ($type === 'camp') {
            $templateFile = __DIR__ . '/templates/participantCamp.latte';
            $camp         = $this->queryBus->handle(new CampQuery(new SkautisCampId($aid)));
            assert($camp instanceof Camp);
            $displayName = $camp->getDisplayName();
            $unitId      = $camp->getUnitId();
        } else {
            $templateFile = __DIR__ . '/templates/participant.latte';
            $event        = $this->queryBus->handle(new EventQuery(new SkautisEventId($aid)));
            assert($event instanceof Event);
            $displayName = $event->getDisplayName();
            $unitId      = $event->getUnitId();
        }

        return $this->templateFactory->create($templateFile, [
            'list' => $service->getParticipants()->getAll($aid),
            'displayName' => $displayName,
            'unitFullNameWithAddress' => $this->units->getOfficialUnit($unitId->toInt())->getFullDisplayNameWithAddress(),
        ]);
    }

    /**
     * vrací pokladní knihu
     */
    public function getCashbook(CashbookId $cashbookId, string $cashbookName, PaymentMethod $paymentMethod) : string
    {
        $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));

        assert($cashbook instanceof Cashbook);

        $cashbook->getType()->getSkautisObjectType();

        return $this->templateFactory->create(__DIR__ . '/templates/cashbook.latte', [
            'header'  => ($paymentMethod->equals(PaymentMethod::CASH()) ? 'Pokladní kniha' : 'Bankovní transakce') . ' - ' . $cashbookName,
            'prefix'  => $cashbook->getChitNumberPrefix(),
            'chits'   => $this->queryBus->handle(ChitListQuery::withMethod($paymentMethod, $cashbookId)),
            'unit'    => $this->queryBus->handle(new CashbookOfficialUnitQuery($cashbookId)),
        ]);
    }

    /**
     * vrací seznam dokladů
     */
    public function getChitlist(CashbookId $cashbookId) : string
    {
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $cashbookId));

        return $this->templateFactory->create(__DIR__ . '/templates/chitlist.latte', [
            'list' => array_filter($chits, function (Chit $chit) : bool {
                return ! $chit->isIncome();
            }),
        ]);
    }

    public function getEventReport(int $skautisEventId, EventEntity $eventService) : string
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
        /** @var Category[] $categories */
        $categories = $this->queryBus->handle(new CategoryListQuery($cashbookId));

        foreach ($categories as $category) {
            if (in_array($category->getId(), [ICategory::CATEGORY_HPD_ID, ICategory::CATEGORY_REFUND_ID], true)) {
                continue;
            }

            $virtual   = $category->isVirtual() ? self::CATEGORY_VIRTUAL:self::CATEGORY_REAL;
            $operation = $category->getOperationType()->getValue();

            $sums[$virtual][$operation][$category->getId()] = [
                'amount' => MoneyFactory::toFloat($category->getTotal()),
                'label' => $category->getName(),
            ];
        }

        $totalIncome = array_sum(
            array_column($sums[self::CATEGORY_REAL][Operation::INCOME], 'amount')
        );

        $totalExpense = array_sum(
            array_column($sums[self::CATEGORY_REAL][Operation::EXPENSE], 'amount')
        );

        $virtualTotalIncome = array_sum(
            array_column($sums[self::CATEGORY_VIRTUAL][Operation::INCOME], 'amount')
        );

        $virtualTotalExpense = array_sum(
            array_column($sums[self::CATEGORY_VIRTUAL][Operation::EXPENSE], 'amount')
        );

        $participants = $eventService->getParticipants()->getAll($skautisEventId);
        $personDays   = $eventService->getParticipants()->getPersonsDays($participants);
        $events       = $this->events->find(new SkautisEventId($skautisEventId));
        $functions    = $this->queryBus->handle(new EventFunctions(new SkautisEventId($skautisEventId)));

        return $this->templateFactory->create(__DIR__ . '/templates/eventReport.latte', [
            'participantsCnt' => count($participants),
            'personsDays' => $personDays,
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

    public function getCampReport(int $skautisCampId, EventEntity $campService, bool $areTotalsConsistentWithSkautis) : string
    {
        $cashbookId = $this->queryBus->handle(new CampCashbookIdQuery(new SkautisCampId($skautisCampId)));
        $categories = $this->queryBus->handle(new CategoryListQuery($cashbookId));

        $total = [
            'income'  => MoneyFactory::zero(),
            'expense' => MoneyFactory::zero(),
            'virtualIncome'  => MoneyFactory::zero(),
            'virtualExpense' => MoneyFactory::zero(),
        ];

        $incomeCategories  = [self::CATEGORY_REAL => [], self::CATEGORY_VIRTUAL => []];
        $expenseCategories = [self::CATEGORY_REAL => [], self::CATEGORY_VIRTUAL => []];

        foreach ($categories as $category) {
            assert($category instanceof Category);

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

        $participants = $campService->getParticipants()->getAll($skautisCampId);

        return $this->templateFactory->create(__DIR__ . '/templates/campReport.latte', [
            'participantsCnt' => count($participants),
            'personsDays' => $campService->getParticipants()->getPersonsDays($participants),
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
        ]);
    }
}
