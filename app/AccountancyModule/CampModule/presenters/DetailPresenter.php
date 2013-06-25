<?php

namespace AccountancyModule\CampModule;

/**
 * @author Hána František
 * akce
 */
class DetailPresenter extends BasePresenter {

    public function renderDefault($aid) {
        $this->template->funkce = $this->isAllowed("EV_EventFunction_ALL_EventCamp") ? $this->context->campService->event->getFunctions($aid) : false;
        $this->template->accessDetail = $this->isAllowed(self::STable . "_DETAIL");
        $this->template->skautISUrl = $this->context->skautIS->getHttpPrefix() . ".skaut.cz/";
        if ($this->isAjax()) {
            $this->invalidateControl("contentSnip");
        }
    }

    public function renderReport($aid) {
        if (!$this->isAllowed("EV_EventFunction_ALL_EventCamp")) {
            $this->flashMessage("Nemáte právo přistupovat k táboru", "warning");
            $this->redirect("default", array("aid" => $aid));
        }
        if(!$this->context->campService->chits->isConsistent($aid)){
            $this->flashMessage("Data v účtech a ve skautisu jsou nekonzistentní!", "warning");
            $this->redirect("default", array("aid" => $aid));
        }
        
        $template = $this->context->exportService->getCampReport($aid, $this->context->campService);
        $this->context->eventService->participants->makePdf($template, "reportCamp.pdf");
        $this->terminate();
    }

}

