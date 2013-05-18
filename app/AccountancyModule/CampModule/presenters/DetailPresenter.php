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
    }

    public function renderReport($aid) {
        if (!$this->isAllowed("EV_EventGeneral_DETAIL")) {
            $this->flashMessage("Nemáte právo přistupovat k akci", "warning");
            $this->redirect("default", array("aid" => $aid));
        }
        $template = $this->context->exportService->getEventReport($aid, $this->context->eventService);

        $this->context->eventService->participants->makePdf($template, "report.pdf");
        $this->terminate();
    }

}

