<?php

namespace App\AccountancyModule\UnitAccountModule;

class CashbookPresenter extends BasePresenter
{

    use \CashbookTrait;

    protected function startup() : void
    {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Musíš vybrat jednotku", "danger");
            $this->redirect("Default:");
        }
        $this->entityService = $this->context->getService("unitAccountService");


        $this->event = $this->entityService->event->get($this->aid);

        if (!$this->isReadable) {
            $this->flashMessage("Nemáš oprávnění číst data jednotky", "danger");
            $this->redirect("Default:");
        }

        $this->redrawControl('chitForm');
        $this->template->unitPairs = $this->unitService->getReadUnits($this->user);
    }

    public function renderDefault(int $aid, $dp = FALSE) : void
    {
        $this->template->isInMinus = FALSE; // musi byt v before render aby se vyhodnotila az po handleru

        $this->fillTemplateVariables();
        $this->template->list = $this->entityService->chits->getAll($aid);
        if ($this->isAjax()) {
            $this->redrawControl("contentSnip");
        }
    }

}
