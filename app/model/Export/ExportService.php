<?php

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
        $template->registerHelperLoader('TemplateHelpers::loader');
        $template->registerHelperLoader('AccountancyHelpers::loader');
        $template->registerFilter(new LatteFilter());
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
    public function getParticipants($aid, EventEntity $service) {
        $template = $this->getTemplate(dirname(__FILE__) . '/templates/participant.latte');
        $template->info = $service->event->get($aid);
        $template->list = $service->participants->getAllDetail($aid, $service->participants->getAll($aid));
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

    /**
     * vrací hromadný příjmový doklad
     * @param type $aid
     * @param EventEntity $service
     * @param BaseService $unitService
     * @return type
     */
    public function getHpd($aid, EventEntity $service, BaseService $unitService) {
        $template = $this->getTemplate(dirname(__FILE__) . "/templates/hpd.latte");
        $template->oficialName = $unitService->getOficialName($service->event->get($aid)->ID_Unit);
        $template->totalPayment = $service->participants->getTotalPayment($aid);
        $template->list = $service->participants->getAll($aid);
        return $template;
    }

    /**
     * vrací PDF s vybranými paragony
     * @param type $unitService
     * @param type $template
     * @param type $actionInfo
     * @param type $chits
     * @param type $fileName 
     */
    public function getChits($aid, EventEntity $eventService, BaseService $unitService, array $chits) {
        
        $income = array();
        $outcome = array();
        foreach ($chits as $c) {
            if ($c->ctype == "in") {
                $income[] = $c;
                continue;
            }
            $outcome[] = $c;
        }
        $template = $this->getTemplate(dirname(__FILE__) . "/templates/chits.latte");
        
        $template->income = $income;
        $template->outcome = $outcome;
        $template->oficialName = $unitService->getOficialName($eventService->event->get($aid)->ID_Unit);
        return $template;
    }

}

