<?php

namespace Model;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\ObjectType;
use Model\Cashbook\Operation;
use Model\Cashbook\Repositories\ICategoryRepository;
use Model\Event\Functions;
use Model\Event\ReadModel\Queries\CampFunctions;
use Model\Event\ReadModel\Queries\EventFunctions;
use Model\Event\Repositories\IEventRepository;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;
use Model\Services\TemplateFactory;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class ExportService
{

    /** @var UnitService */
    private $units;

    /** @var ICategoryRepository */
    private $categories;

    /** @var TemplateFactory */
    private $templateFactory;

    /** @var IEventRepository */
    private $events;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(
        UnitService $units,
        ICategoryRepository $categories,
        TemplateFactory $templateFactory,
        IEventRepository $events,
        QueryBus $queryBus
    )
    {
        $this->units = $units;
        $this->categories = $categories;
        $this->templateFactory = $templateFactory;
        $this->events = $events;
        $this->queryBus = $queryBus;
    }

    public function getNewPage()
    {
        return '<pagebreak type="NEXT-ODD" resetpagenum="1" pagenumstyle="i" suppress="off" />';
    }

    /**
     * vrací seznam účastníků
     */
    public function getParticipants($aid, EventEntity $service, $type = 'general'): string
    {
        $templateFile = __DIR__ . '/templates/participant' . ($type == 'camp' ? 'Camp' : '') . '.latte';

        return $this->templateFactory->create($templateFile, [
            'list' => $service->participants->getAll($aid),
            'info' => $service->event->get($aid),
        ]);
    }

    /**
     * vrací pokladní knihu
     */
    public function getCashbook(int $skautisEventId, EventEntity $service): string
    {
        return $this->templateFactory->create(__DIR__ . '/templates/cashbook.latte', [
            'list' => $service->chits->getAll($skautisEventId),
            'info' => $service->event->get($skautisEventId),
        ]);
    }

    /**
     * vrací seznam dokladů
     */
    public function getChitlist(int $skautisEventId, EventEntity $service): string
    {
        return $this->templateFactory->create(__DIR__ . '/templates/chitlist.latte', [
            'list' => array_filter($service->chits->getAll($skautisEventId), function ($c) {
                return $c->ctype == "out";
            }),
        ]);
    }

    /**
     * @throws Event\EventNotFoundException
     */
    public function getEventReport(int $skautisEventId, EventEntity $eventService): string
    {
        $categories = $this->categories->findByObjectType(ObjectType::get(ObjectType::EVENT));

        $sums = [
            Operation::INCOME => [],
            Operation::EXPENSE => [],
        ];

        foreach ($categories as $category) {
            $operation = $category->getOperationType()->getValue();
            $sums[$operation][$category->getId()] = [
                'amount' => 0,
                'label' => $category->getName(),
            ];
        }

        //rozpočítává paragony do jednotlivých skupin
        foreach ($eventService->chits->getAll($skautisEventId) as $chit) {
            $sums[$chit->ctype][$chit->category]['amount'] += $chit->price;
        }

        $totalIncome = array_sum(
            array_column($sums[Operation::INCOME], 'amount')
        );

        $totalExpense = array_sum(
            array_column($sums[Operation::EXPENSE], 'amount')
        );

        $participants = $eventService->participants->getAll($skautisEventId);

        return $this->templateFactory->create(__DIR__ . '/templates/eventReport.latte', [
            'participantsCnt' => count($participants),
            'personsDays' => $eventService->participants->getPersonsDays($participants),
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
     */
    public function getChits(int $aid, EventEntity $eventService, array $chits): string
    {
        $income = [];
        $outcome = [];
        $activeHpd = FALSE;

        foreach ($chits as $c) {
            if ($c->cshort == "hpd") {
                $activeHpd = TRUE;
            }
            switch ($c->ctype) {
                case "out":
                    $outcome[] = $c;
                    break;
                case "in":
                    $income[] = $c;
                    break;
                default:
                    throw new \Nette\InvalidStateException("Neznámý typ paragou: " . $c->ctype);
            }
        }
        $event = $eventService->event->get($aid);

        $template = [];

        if (in_array($eventService->event->type, ["camp", "general"])) {
            $template['oficialName'] = $this->units->getOficialName($event->ID_Unit);
        } elseif ($eventService->event->type == "unit") {
            $template['oficialName'] = $this->units->getOficialName($event->ID);
        } else {
            throw new \Nette\InvalidArgumentException("Neplatný typ události v ExportService");
        }
        //HPD 
        if ($activeHpd) {
            $template['totalPayment'] = $eventService->participants->getTotalPayment($aid);

            $functionsQuery = $eventService->event->type === 'camp'
                ? new CampFunctions(new SkautisCampId($aid))
                : new EventFunctions(new SkautisEventId($aid));

            /** @var Functions $functions */
            $functions = $this->queryBus->handle($functionsQuery);
            $accountant = $functions->getAccountant() ?? $functions->getLeader();
            $template['pokladnik'] = $accountant !== NULL ? $accountant->getName() : '';

            $template['list'] = $eventService->participants->getAll($aid);
        }

        $template['event'] = $event;
        $template['income'] = $income;
        $template['outcome'] = $outcome;

        return $this->templateFactory->create(__DIR__ . '/templates/chits.latte', $template);
    }

    public function getCampReport(int $skautisCampId, EventEntity $campService): string
    {
        $categories = [];
        foreach ($campService->chits->getCategories($skautisCampId) as $c) {
            $categories[$c->IsRevenue ? "in" : "out"][$c->ID] = $c;
        }

        $participants = $campService->participants->getAll($skautisCampId);

        return $this->templateFactory->create(__DIR__ . '/templates/campReport.latte', [
            'participantsCnt' => count($participants),
            'personsDays' => $campService->participants->getPersonsDays($participants),
            'a' => $campService->event->get($skautisCampId),
            'chits' => $categories,
            'functions' => $this->queryBus->handle(new CampFunctions(new SkautisCampId($skautisCampId))),
        ]);
    }

}
