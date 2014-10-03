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
     * @param type $fileName
     * @return \FileTemplate
     */
    protected function setTemplate(ITemplate &$template, $fileName) {
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
    public function getParticipants(ITemplate $template, $aid, EventEntity $service, $type = "generalEvent") {
        if ($type == "camp") {
            //$template = $this->getTemplate(dirname(__FILE__) . '/templates/participantCamp.latte');
            $this->setTemplate($template, dirname(__FILE__) . '/templates/participantCamp.latte');
            $template->list = $service->participants->getAll($aid);
        } else {
            //$template = $this->getTemplate(dirname(__FILE__) . '/templates/participant.latte');
            $this->setTemplate($template, dirname(__FILE__) . '/templates/participant.latte');
            $template->list = $service->participants->getAll($aid);
        }
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
     * 
     * @param type $aid
     * @param EventEntity $eventService
     * @return type
     */
    public function getEventReport(ITemplate $template, $aid, EventEntity $eventService) {
        $categories = array();
        //inicializuje pole s kategorií s částkami na 0
        foreach (ArrayHash::from($eventService->chits->getCategories()) as $c) {
            $categories[$c->type][$c->short] = $c;
            $categories[$c->type][$c->short]->price = 0;
        }

        //rozpočítává paragony do jednotlivých skupin
        foreach ($eventService->chits->getAll($aid) as $chit) {
            $categories[$chit->ctype][$chit->cshort]->price += $chit->price;
        }
        $this->setTemplate($template, dirname(__FILE__) . '/templates/eventReport.latte');
        $template->participants = $eventService->participants->getAll($aid);
        $template->personsDays = $eventService->participants->getPersonsDays($aid);
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
        foreach ($chits as $c) {
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
        $this->setTemplate($template, dirname(__FILE__) . '/templates/chits.latte');

        //HPD 
        $template->totalPayment = $eventService->participants->getTotalPayment($aid);
        $func = $eventService->event->getFunctions($aid);
        $template->pokladnik = ($func[2]->ID_Person != null) ? $func[2]->Person : $func[0]->Person;
        $template->list = $eventService->participants->getAll($aid);

        $template->event = $eventService->event->get($aid);
        $template->income = $income;
        $template->outcome = $outcome;
        $template->oficialName = $unitService->getOficialName($eventService->event->get($aid)->ID_Unit);
        return $template;
    }

    public function getCampReport(ITemplate $template, $aid, EventEntity $campService) {
        $categories = array();
        foreach ($campService->chits->getCategories($aid) as $c) {
            $categories[$c->IsRevenue ? "in" : "out"][$c->ID] = $c;
        }

        $this->setTemplate($template, dirname(__FILE__) . '/templates/campReport.latte');
        $template->participants = $campService->participants->getAll($aid);
        $template->personsDays = $campService->participants->getPersonsDays($aid);
        $template->a = $campService->event->get($aid);
        $template->chits = $categories;
        $template->func = $campService->event->getFunctions($aid);
        return $template;
    }

}
