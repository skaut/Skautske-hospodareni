<?php

/**
 * @author sinacek
 */
class Accountancy_ExportPresenter extends Accountancy_BasePresenter {

    function startup() {
        parent::startup();
        
        if (!$this->aid) {
            $this->flashMessage("Nejdřív musíš vybrat akci", "danger");
            $this->redirect("Event:");
        }   
    }

    public function renderDefault($aid) {
        
    }

    public function renderChits($aid) {
        $template = $this->template;
        $template->registerHelper('priceToString', 'AccountancyHelpers::priceToString');
        $template->setFile(dirname(__FILE__) . '/../templates/Export/ex.chits.latte');
        $template->list = $this->context->chitService->getAllOut($aid);
        $template->oficialName = $this->context->unitService->getOficialName($info->ID_Unit);
        $this->context->chitService->makePdf($template, Strings::webalize($info->DisplayName) . "_paragony.pdf");
        $this->terminate();
    }

    public function renderCashbook($aid) {
        $list = $this->context->chitService->getAll($aid);

        $template = $this->template;
        $template->setFile(dirname(__FILE__) . '/../templates/Export/ex.cashbook.latte');
        $template->registerHelper('price', 'AccountancyHelpers::price');
        $template->list = $list;
        $template->info = $info;
        $this->context->chitService->makePdf($template, Strings::webalize($info->DisplayName) . "_pokladni-kniha.pdf");
        $this->terminate();
    }

    public function renderMassIn($aid) {
        $list = $this->context->participantService->getAllParticipants($aid);
        $info = $this->context->eventService->get($this->aid);
        
        $template = $this->template;
        $template->setFile(dirname(__FILE__) . '/../templates/Export/ex.massIn.latte');
        $template->list = $list;
        $template->totalPrice = $this->context->participantService->getTotalPayment($aid);
        $template->oficialName = $this->context->unitService->getOficialName($info->ID_Unit);

        $this->context->participantService->makePdf($template, Strings::webalize($info->DisplayName) . "_hpd.pdf");
        $this->terminate();
    }

    public function renderReport($aid) {
        $info = $this->context->eventService->get($aid);
        $participants = $this->context->participantService->getAllParticipants($aid);
        $chitsAll = $this->context->chitService->getAll($aid);

        //inicializuje pole s kategorií s částkami na 0
        foreach (ArrayHash::from($this->context->chitService->getCategories($all = TRUE)) as $c) {
            $categories[$c->type][$c->short] = $c;
            $categories[$c->type][$c->short]->price = 0;
        }
        
        //rozpočítává paragony do jednotlivých skupin
        foreach ($chitsAll as $chit) {
            $categories[$chit->ctype][$chit->cshort]->price += $chit->price;
        }

        $template = $this->template;
        $template->setFile(dirname(__FILE__) . '/../templates/Export/ex.report.latte');
        $template->registerHelper('price', 'AccountancyHelpers::price');
        $template->participants = $participants;
        $template->personsDays = $this->context->participantService->getPersonsDays($this->aid);
        $template->a = $info;
        $template->chits = $categories;
        $template->func = $this->context->eventService->getFunctions($aid);
        $this->context->participantService->makePdf($template, Strings::webalize($info->DisplayName) . "_report.pdf");
        $this->terminate();
    }

}
