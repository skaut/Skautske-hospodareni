<?php

use Nette\ArrayHash,
    Nette\Templating\FileTemplate;

/**
 * @author Hána František
 */
class ExportService extends BaseService {

    /**
     * vrací připravenou template s filtry a helpery
     * @param type $fileName
     * @return \FileTemplate
     */
    protected function getTemplate($fileName) {
        $template = new FileTemplate();
        $template->setFile($fileName);
        $template->registerHelperLoader('Nette\Templating\Helpers::loader');
        $template->registerHelperLoader('AccountancyHelpers::loader');
        $template->registerFilter(new Nette\Latte\Engine);
        return $template;
    }

    public function getNewPage() {
        return '<pagebreak type="NEXT-ODD" resetpagenum="1" pagenumstyle="i" suppress="off" />';
    }

    /**
     * vrací seznam účastníků
     * @param type $aid - ID akce
     * @param EventEntity $service
     */
    public function getParticipants($aid, EventEntity $service, $type = "generalEvent") {
        if ($type == "camp") {
            $template = $this->getTemplate(dirname(__FILE__) . '/templates/participantCamp.latte');
            $template->list = $service->participants->getAllPersonDetail($aid, $service->participants->getAllWithDetails($aid));
        } else {
            $template = $this->getTemplate(dirname(__FILE__) . '/templates/participant.latte');
            $template->list = $service->participants->getAllPersonDetail($aid, $service->participants->getAll($aid));
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
    public function getCashbook($aid, EventEntity $service) {
        $template = $this->getTemplate(dirname(__FILE__) . '/templates/cashbook.latte');
        $template->list = $service->chits->getAll($aid);
        $template->info = $service->event->get($aid);
        return $template;
    }

    /**
     * 
     * @param type $aid
     * @param EventEntity $eventSerice
     * @return type
     */
    public function getEventReport($aid, EventEntity $eventSerice) {
        $categories = array();
        //inicializuje pole s kategorií s částkami na 0
        foreach (ArrayHash::from($eventSerice->chits->getCategories($all = TRUE)) as $c) {
            $categories[$c->type][$c->short] = $c;
            $categories[$c->type][$c->short]->price = 0;
        }

        //rozpočítává paragony do jednotlivých skupin
        foreach ($eventSerice->chits->getAll($aid) as $chit) {
            $categories[$chit->ctype][$chit->cshort]->price += $chit->price;
        }

        $template = $this->getTemplate(dirname(__FILE__) . '/templates/eventReport.latte');
        $template->participants = $eventSerice->participants->getAll($aid);
        $template->personsDays = $eventSerice->participants->getPersonsDays($aid);
        $template->a = $eventSerice->event->get($aid);
        $template->chits = $categories;
        $template->func = $eventSerice->event->getFunctions($aid);
        return $template;
    }

//    /**
//     * vrací hromadný příjmový doklad
//     * @param type $aid
//     * @param EventEntity $service
//     * @param BaseService $unitService
//     * @return type
//     */
//    public function getHpd($aid, EventEntity $service, BaseService $unitService) {
//        $template = $this->getTemplate(dirname(__FILE__) . "/templates/hpd.latte");
//        $event = $template->event = $service->event->get($aid);
//        $template->oficialName = $unitService->getOficialName($event->ID_Unit);
//        $template->totalPayment = $service->participants->getTotalPayment($aid);
//        $func = $service->event->getFunctions($aid);
//        $template->hospodar = ($func[2]->ID_Person != null) ? $func[2]->Person : $func[0]->Person;
//
//        $template->list = $service->participants->getAll($aid);
//        return $template;
//    }

    /**
     * vrací PDF s vybranými paragony
     * @param type $unitService
     * @param type $template
     * @param type $actionInfo
     * @param type $chits
     * @param type $fileName 
     */
    public function getChits($aid, EventEntity $eventService, BaseService $unitService, array $chits, $type = "eventGeneral") {

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
                    throw new InvalidStateException("Neznámý typ paragou");
                    break;
            }
        }
        $template = $this->getTemplate(dirname(__FILE__) . "/templates/chits.latte");

        if ($type != "camp") {
            //HPD alias ctype == pp
            $template->totalPayment = $eventService->participants->getTotalPayment($aid);
            $func = $eventService->event->getFunctions($aid);
            $template->pokladnik = ($func[2]->ID_Person != null) ? $func[2]->Person : $func[0]->Person;
            $template->list = $eventService->participants->getAll($aid);
        }

        $template->income = $income;
        $template->outcome = $outcome;
        $template->oficialName = $unitService->getOficialName($eventService->event->get($aid)->ID_Unit);
        return $template;
    }

    public function getCampReport($aid, EventEntity $campSerice) {
        $categories = array();
        foreach ($campSerice->chits->getCategoriesCamp($aid) as $c) {
            $categories[$c->IsRevenue ? "in" : "out"][$c->ID] = $c;
        }

        $template = $this->getTemplate(dirname(__FILE__) . '/templates/campReport.latte');
        $template->participants = $campSerice->participants->getAll($aid);
        $template->personsDays = $campSerice->participants->getPersonsDays($aid);
        $template->a = $campSerice->event->get($aid);
        $template->chits = $categories;
        $template->func = $campSerice->event->getFunctions($aid);
        return $template;
    }

}

