<?php

declare(strict_types=1);

namespace Model;

use Doctrine\Common\Collections\ArrayCollection;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ICategory;
use Model\Cashbook\ObjectType;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Cashbook\Repositories\IStaticCategoryRepository;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Cashbook\Category;
use Model\DTO\Cashbook\Chit;
use Model\Event\Functions;
use Model\Event\ReadModel\Queries\CampFunctions;
use Model\Event\ReadModel\Queries\EventFunctions;
use Model\Event\Repositories\IEventRepository;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;
use Model\Services\TemplateFactory;
use Model\Utils\MoneyFactory;
use function array_column;
use function array_filter;
use function array_sum;
use function array_values;
use function count;

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
        $templateFile = __DIR__ . '/templates/participant' . ($type === 'camp' ? 'Camp' : '') . '.latte';

        return $this->templateFactory->create($templateFile, [
            'list' => $service->getParticipants()->getAll($aid),
            'info' => $service->getEvent()->get($aid),
        ]);
    }

    /**
     * vrací pokladní knihu
     */
    public function getCashbook(CashbookId $cashbookId, string $cashbookName) : string
    {
        /** @var Cashbook $cashbook */
        $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));

        return $this->templateFactory->create(__DIR__ . '/templates/cashbook.latte', [
            'cashbookName'  => $cashbookName,
            'prefix'        => $cashbook->getChitNumberPrefix(),
            'chits'         => $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $cashbookId)),
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
                return $chit->getCategory()->getOperationType()->equalsValue(Operation::EXPENSE);
            }),
        ]);
    }

    /**
     * @throws Event\EventNotFound
     */
    public function getEventReport(int $skautisEventId, EventEntity $eventService) : string
    {
        $categories = $this->categories->findByObjectType(ObjectType::get(ObjectType::EVENT));

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

        foreach ($categories as $category) {
            $virtual                                        = $category->isVirtual() ? self::CATEGORY_VIRTUAL:self::CATEGORY_REAL;
            $operation                                      = $category->getOperationType()->getValue();
            $sums[$virtual][$operation][$category->getId()] = [
                'amount' => 0,
                'label' => $category->getName(),
            ];
        }

        $cashbookId = $this->queryBus->handle(new EventCashbookIdQuery(new SkautisEventId($skautisEventId)));
        /** @var Chit[] $chits */
        $chits = $this->queryBus->handle(ChitListQuery::all($cashbookId));

        //rozpočítává paragony do jednotlivých skupin
        foreach ($chits as $chit) {
            $category                                                      = $chit->getCategory();
            $operationType                                                 = $category->getOperationType()->getValue();
            $virtual                                                       = $category->isVirtual() ? self::CATEGORY_VIRTUAL : self::CATEGORY_REAL;
            $sums[$virtual][$operationType][$category->getId()]['amount'] += $chit->getBody()->getAmount()->toFloat();
        }

        /* sum up "Příjmy od účastníků"(1) and "Hromadný příjem od úč."(11) */
        $sums[self::CATEGORY_REAL][Operation::INCOME][ICategory::CATEGORY_PARTICIPANT_INCOME_ID]['amount'] += $sums[self::CATEGORY_REAL][Operation::INCOME][ICategory::CATEGORY_HPD_ID]['amount'];
        unset($sums[self::CATEGORY_REAL][Operation::INCOME][ICategory::CATEGORY_HPD_ID]);

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

        return $this->templateFactory->create(__DIR__ . '/templates/eventReport.latte', [
            'participantsCnt' => count($participants),
            'personsDays' => $eventService->getParticipants()->getPersonsDays($participants),
            'event' => $this->events->find(new SkautisEventId($skautisEventId)),
            'chits' => $sums,
            'functions' => $this->queryBus->handle(new EventFunctions(new SkautisEventId($skautisEventId))),
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

    /**
     * vrací PDF s vybranými paragony
     * @param Chit[] $chits
     */
    public function getChits(
        int $aid,
        EventEntity $eventService,
        array $chits,
        CashbookId $cashbookId
    ) : string {
        $chitsCollection = new ArrayCollection($chits);

        [$income, $outcome] = $chitsCollection->partition(function ($_, Chit $chit) : bool {
            return $chit->getCategory()->getOperationType()->equalsValue(Operation::INCOME);
        });

        $activeHpd = $chitsCollection->exists(function ($_, Chit $chit) : bool {
            return $chit->getCategory()->getShortcut() === 'hpd';
        });

        /** @var Cashbook $cashbook */
        $cashbook     = $this->queryBus->handle(new CashbookQuery($cashbookId));
        $cashbookType = $cashbook->getType();

        $template = [];

        $event                    = $eventService->getEvent()->get($aid);
        $unitId                   = $cashbookType->isUnit() ? $event->ID : $event->ID_Unit;
        $template['officialName'] = $this->units->getOfficialName($unitId);
        $template['cashbook']     = $cashbook;

        //HPD
        if ($activeHpd) {
            $template['totalPayment'] = $eventService->getParticipants()->getTotalPayment($aid);

            $functionsQuery = $cashbookType->equalsValue(CashbookType::CAMP)
                ? new CampFunctions(new SkautisCampId($aid))
                : new EventFunctions(new SkautisEventId($aid));

            /** @var Functions $functions */
            $functions             = $this->queryBus->handle($functionsQuery);
            $accountant            = $functions->getAccountant() ?? $functions->getLeader();
            $template['pokladnik'] = $accountant !== null ? $accountant->getName() : '';

            $template['list'] = $eventService->getParticipants()->getAll($aid);
        }

        $template['event']   = $event;
        $template['income']  = $income;
        $template['outcome'] = $outcome;

        return $this->templateFactory->create(__DIR__ . '/templates/chits.latte', $template);
    }

    public function getCampReport(int $skautisCampId, EventEntity $campService, bool $areTotalsConsistentWithSkautis) : string
    {
        $cashbookId = $this->queryBus->handle(new CampCashbookIdQuery(new SkautisCampId($skautisCampId)));

        /** @var Category[] $categories */
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
            $virtualCategory = $category->isVirtual() ? self::CATEGORY_VIRTUAL : self::CATEGORY_REAL;

            if ($category->isIncome()) {
                $key         = $category->isVirtual() ? 'virtualIncome' : 'income';
                $total[$key] = $total[$key]->add($category->getTotal());

                $incomeCategories[$virtualCategory][] = $category;
            } else {
                $key         = $category->isVirtual() ? 'virtualExpense' : 'expense';
                $total[$key] = $total[$key]->add($category->getTotal());

                $expenseCategories[$virtualCategory][] = $category;
            }
        }

        $participants = $campService->getParticipants()->getAll($skautisCampId);

        return $this->templateFactory->create(__DIR__ . '/templates/campReport.latte', [
            'participantsCnt' => count($participants),
            'personsDays' => $campService->getParticipants()->getPersonsDays($participants),
            'a' => $campService->getEvent()->get($skautisCampId),
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
