<?php

declare(strict_types=1);

namespace Model;

use Doctrine\Common\Collections\ArrayCollection;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Cashbook\PaymentMethod;
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
            'list' => $service->participants->getAll($aid),
            'info' => $service->event->get($aid),
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
            Operation::INCOME => [],
            Operation::EXPENSE => [],
        ];

        foreach ($categories as $category) {
            $operation                            = $category->getOperationType()->getValue();
            $sums[$operation][$category->getId()] = [
                'amount' => 0,
                'label' => $category->getName(),
            ];
        }

        $cashbookId = $this->queryBus->handle(new EventCashbookIdQuery(new SkautisEventId($skautisEventId)));
        /** @var Chit[] $chits */
        $chits = $this->queryBus->handle(ChitListQuery::all($cashbookId));

        //rozpočítává paragony do jednotlivých skupin
        foreach ($chits as $chit) {
            $category                                                                       = $chit->getCategory();
            $sums[$category->getOperationType()->getValue()][$category->getId()]['amount'] += $chit->getBody()
                ->getAmount()->toFloat();
        }

        $totalIncome = array_sum(
            array_column($sums[Operation::INCOME], 'amount')
        );

        $totalExpense = array_sum(
            array_column($sums[Operation::EXPENSE], 'amount')
        );

        $participants = $eventService->getParticipants()->getAll($skautisEventId);

        return $this->templateFactory->create(__DIR__ . '/templates/eventReport.latte', [
            'participantsCnt' => count($participants),
            'personsDays' => $eventService->getParticipants()->getPersonsDays($participants),
            'event' => $this->events->find($skautisEventId),
            'chits' => $sums,
            'functions' => $this->queryBus->handle(new EventFunctions(new SkautisEventId($skautisEventId))),
            'incomes' => array_values($sums[Operation::INCOME]),
            'expenses' => array_values($sums[Operation::EXPENSE]),
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
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
        $chitsCollection    = new ArrayCollection($chits);

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

        $event                   = $eventService->getEvent()->get($aid);
        $unitId                  = $cashbookType->isUnit() ? $event->ID : $event->ID_Unit;
        $template['oficialName'] = $this->units->getOficialName($unitId);

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

    public function getCampReport(int $skautisCampId, EventEntity $campService) : string
    {
        $cashbookId = $this->queryBus->handle(new CampCashbookIdQuery(new SkautisCampId($skautisCampId)));

        /** @var Category[] $categories */
        $categories = $this->queryBus->handle(new CategoryListQuery($cashbookId));

        $totalIncome  = MoneyFactory::zero();
        $totalExpense = MoneyFactory::zero();

        $incomeCategories  = [];
        $expenseCategories = [];

        foreach ($categories as $category) {
            if ($category->isIncome()) {
                $totalIncome        = $totalIncome->add($category->getTotal());
                $incomeCategories[] = $category;
            } else {
                $totalExpense        = $totalExpense->add($category->getTotal());
                $expenseCategories[] = $category;
            }
        }

        $participants = $campService->getParticipants()->getAll($skautisCampId);

        return $this->templateFactory->create(__DIR__ . '/templates/campReport.latte', [
            'participantsCnt' => count($participants),
            'personsDays' => $campService->getParticipants()->getPersonsDays($participants),
            'a' => $campService->getEvent()->get($skautisCampId),
            'incomeCategories' => $incomeCategories,
            'expenseCategories' => $expenseCategories,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'functions' => $this->queryBus->handle(new CampFunctions(new SkautisCampId($skautisCampId))),
        ]);
    }
}
