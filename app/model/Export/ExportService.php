<?php

namespace Model;

use Model\Cashbook\ObjectType;
use Model\Cashbook\Operation;
use Model\Cashbook\Repositories\ICategoryRepository;
use Model\Services\TemplateFactory;
use Nette\Bridges\ApplicationLatte\Template;

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

    public function __construct(UnitService $units, ICategoryRepository $categories, TemplateFactory $templateFactory)
    {
        $this->units = $units;
        $this->categories = $categories;
        $this->templateFactory = $templateFactory;
    }

    /**
     * @deprecated use TemplateFactory
     * donastavuje helpery a zdrojový file do šablony
     * @param Template $template
     * @param string $fileName
     * @return Template
     */
    protected function setTemplate(Template $template, string $fileName): void
    {
        $template->setFile($fileName);
        $template->getLatte()->addFilter(NULL, '\App\AccountancyModule\AccountancyHelpers::loader');
    }

    public function getNewPage()
    {
        return '<pagebreak type="NEXT-ODD" resetpagenum="1" pagenumstyle="i" suppress="off" />';
    }

    /**
     * vrací seznam účastníků
     * @param Template $template
     * @param int $aid - ID akce
     * @param EventEntity $service
     * @param string $type
     * @return Template
     */
    public function getParticipants(Template $template, $aid, EventEntity $service, $type = "general")
    {
        $this->setTemplate($template, dirname(__FILE__) . "/templates/participant" . ($type == "camp" ? "Camp" : "") . ".latte");
        $template->list = $service->participants->getAll($aid);
        $template->info = $service->event->get($aid);
        return $template;
    }

    /**
     * vrací pokladní knihu
     * @param Template $template
     * @param int $aid - ID akce
     * @param EventEntity $service
     * @return Template
     */
    public function getCashbook(Template $template, $aid, EventEntity $service)
    {
        $this->setTemplate($template, dirname(__FILE__) . '/templates/cashbook.latte');
        $template->list = $service->chits->getAll($aid);
        $template->info = $service->event->get($aid);
        return $template;
    }

    /**
     * vrací seznam dokladů
     * @param Template $template
     * @param int $aid - ID akce
     * @param EventEntity $service
     * @return Template
     */
    public function getChitlist(Template $template, $aid, EventEntity $service)
    {
        $this->setTemplate($template, dirname(__FILE__) . '/templates/chitlist.latte');
        $template->list = array_filter($service->chits->getAll($aid), function ($c) {
            return $c->ctype == "out";
        });
        return $template;
    }

    public function getEventReport(int $aid, EventEntity $eventService): string
    {
        $categories = $this->categories->findByObjectType(ObjectType::get(ObjectType::EVENT));

        $sums = [
            Operation::INCOME => [],
            Operation::EXPENSE => [],
        ];

        foreach ($categories as $category) {
            $operation = $category->getOperationType()->getValue();
            $sums[$operation][] = [
                'amount' => 0,
                'label' => $category->getName(),
            ];
        }

        //rozpočítává paragony do jednotlivých skupin
        foreach ($eventService->chits->getAll($aid) as $chit) {
            $sums[$chit->ctype][$chit->category]['amount'] += $chit->price;
        }

        $totalIncome = array_sum(
            array_column($sums[Operation::INCOME], 'amount')
        );

        $totalExpense = array_sum(
            array_column($sums[Operation::EXPENSE], 'amount')
        );

        $participants = $eventService->participants->getAll($aid);

        return $this->templateFactory->create(__DIR__ . '/templates/eventReport.latte', [
            'participantsCnt' => count($participants),
            'personsDays' => $eventService->participants->getPersonsDays($participants),
            'a' => $eventService->event->get($aid),
            'chits' => $sums,
            'func' => $eventService->event->getFunctions($aid),
            'incomes' => $sums[Operation::INCOME],
            'expenses' => $sums[Operation::EXPENSE],
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
        ]);
    }

    /**
     * vrací PDF s vybranými paragony
     * @param Template $template
     * @param int $aid
     * @param EventEntity $eventService
     * @param array $chits
     * @return Template
     */
    public function getChits(Template $template, $aid, EventEntity $eventService, array $chits)
    {
        $income = [];
        $outcome = [];
        $activeHpd = FALSE;
        $this->setTemplate($template, dirname(__FILE__) . '/templates/chits.latte');

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
        if (in_array($eventService->event->type, ["camp", "general"])) {
            $template->oficialName = $this->units->getOficialName($event->ID_Unit);
        } elseif ($eventService->event->type == "unit") {
            $template->oficialName = $this->units->getOficialName($event->ID);
        } else {
            throw new \Nette\InvalidArgumentException("Neplatný typ události v ExportService");
        }
        //HPD 
        if ($activeHpd) {
            $template->totalPayment = $eventService->participants->getTotalPayment($aid);
            $func = $eventService->event->getFunctions($aid);
            $template->pokladnik = ($func[2]->ID_Person != NULL) ? $func[2]->Person : (($func[0]->ID_Person != NULL) ? $func[0]->Person : "");
            $template->list = $eventService->participants->getAll($aid);
        }

        $template->event = $event;
        $template->income = $income;
        $template->outcome = $outcome;
        return $template;
    }

    /**
     * @param Template $template
     * @param int $aid
     * @param EventEntity $campService
     * @return Template
     */
    public function getCampReport(Template $template, $aid, EventEntity $campService)
    {
        $categories = [];
        foreach ($campService->chits->getCategories($aid) as $c) {
            $categories[$c->IsRevenue ? "in" : "out"][$c->ID] = $c;
        }

        $this->setTemplate($template, dirname(__FILE__) . '/templates/campReport.latte');
        $participants = $campService->participants->getAll($aid);
        $template->participantsCnt = count($participants);
        $template->personsDays = $campService->participants->getPersonsDays($participants);
        $template->a = $campService->event->get($aid);
        $template->chits = $categories;
        $template->func = $campService->event->getFunctions($aid);
        return $template;
    }

    private function getCategories(ObjectType $type): array
    {
        $categories = $this->categories->findByObjectType($type);
        $result = [
            Operation::INCOME => [],
            Operation::EXPENSE => [],
        ];

        foreach($categories as $category) {
            $operation = $category->getOperationType()->getValue();
            $result[$operation][$category->getShortcut()] = 0;
        }

        return $categories;
    }

}
