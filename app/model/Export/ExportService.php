<?php

namespace Model;

use \Nette\ArrayHash,
    Nette\Application\UI\ITemplate;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class ExportService extends BaseService {

    /**
     * donastavuje helpery a zdrojový file do šablony
     * @param string $fileName
     * @return \FileTemplate
     */
    protected function setTemplate(ITemplate $template, string $fileName) {
        $template->setFile($fileName);
        $template->getLatte()->addFilter(NULL, '\App\AccountancyModule\AccountancyHelpers::loader');
        //return $template;
    }

    public function getNewPage() {
        return '<pagebreak type="NEXT-ODD" resetpagenum="1" pagenumstyle="i" suppress="off" />';
    }

    /**
     * vrací seznam účastníků
     * @param type $aid - ID akce
     * @param EventEntity $service
     */
    public function getParticipants(ITemplate $template, $aid, EventEntity $service, $type = "general") {
        $this->setTemplate($template, dirname(__FILE__) . "/templates/participant" . ($type == "camp" ? "Camp" : "") . ".latte");
        $template->list = $service->participants->getAll($aid);
        $template->info = $service->event->get($aid);
        return $template;
    }

    /**
     * vrací pokladní knihu
     * @param type $aid - ID akce
     * @param EventEntity $service
     * @return \FileTemplate
     */
    public function getCashbook(ITemplate $template, $aid, EventEntity $service) {
        $this->setTemplate($template, dirname(__FILE__) . '/templates/cashbook.latte');
        $template->list = $service->chits->getAll($aid);
        $template->info = $service->event->get($aid);
        return $template;
    }

    /**
     * vrací seznam dokladů
     * @param type $aid - ID akce
     * @param EventEntity $service
     * @return \FileTemplate
     */
    public function getChitlist(ITemplate $template, $aid, EventEntity $service) {
        $this->setTemplate($template, dirname(__FILE__) . '/templates/chitlist.latte');
        $template->list =  array_filter($service->chits->getAll($aid), function($c) {
            return $c->ctype == "out";
        });
        return $template;
    }

    /**
     * 
     * @param type $aid
     * @param EventEntity $eventService
     * @return type
     */
    public function getEventReport(ITemplate $template, $aid, EventEntity $eventService) {
        $categories = array();
        //inicializuje pole s kategorií s částkami na 0
        foreach (ArrayHash::from($eventService->chits->getCategories($aid)) as $c) {
            $categories[$c->type][$c->short] = $c;
            $categories[$c->type][$c->short]->price = 0;
        }

        //rozpočítává paragony do jednotlivých skupin
        foreach ($eventService->chits->getAll($aid) as $chit) {
            $categories[$chit->ctype][$chit->cshort]->price += $chit->price;
        }
        $this->setTemplate($template, dirname(__FILE__) . '/templates/eventReport.latte');
        $participants = $eventService->participants->getAll($aid);
        $template->participantsCnt = count($participants);
        $template->personsDays = $eventService->participants->getPersonsDays($participants);
        $template->a = $eventService->event->get($aid);
        $template->chits = $categories;
        $template->func = $eventService->event->getFunctions($aid);
        return $template;
    }

    /**
     * vrací PDF s vybranými paragony
     * @param type $aid
     * @param type $eventService
     * @param type $unitService
     * @param type $chits
     */
    public function getChits(ITemplate $template, $aid, EventEntity $eventService, BaseService $unitService, array $chits) {
        $income = array();
        $outcome = array();
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
        if (in_array($eventService->event->type, array("camp", "general"))) {
            $template->oficialName = $unitService->getOficialName($event->ID_Unit);
        } elseif ($eventService->event->type == "unit") {
            $template->oficialName = $unitService->getOficialName($event->ID);
        } else {
            throw new \Nette\InvalidArgumentException("Neplatný typ události v ExportService");
        }
        //HPD 
        if ($activeHpd) {
            $template->totalPayment = $eventService->participants->getTotalPayment($aid);
            $func = $eventService->event->getFunctions($aid);
            $template->pokladnik = ($func[2]->ID_Person != null) ? $func[2]->Person : (($func[0]->ID_Person != null) ? $func[0]->Person : "");
            $template->list = $eventService->participants->getAll($aid);
        }

        $template->event = $event;
        $template->income = $income;
        $template->outcome = $outcome;
        return $template;
    }

    public function getCampReport(ITemplate $template, $aid, EventEntity $campService) {
        $categories = array();
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

}
