<?php

namespace App\AccountancyModule\EventModule;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class CashbookPresenter extends BasePresenter
{

    use \CashbookTrait;

    protected function startup() : void
    {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Musíš vybrat akci", "danger");
            $this->redirect("Event:");
        }
        $this->entityService = $this->eventService;

        $ev_state = $this->event->ID_EventGeneralState == "draft" ? TRUE : FALSE;
        $this->isEditable = $this->template->isEditable = $ev_state && array_key_exists("EV_ParticipantGeneral_UPDATE_EventGeneral", $this->availableActions);
        $this->template->missingCategories = FALSE;
    }

    public function renderDefault($aid, $pid = NULL, $dp = FALSE) : void
    {
        if ($pid !== NULL) {
            $this->editChit($pid);
        }

        $this->template->isInMinus = $this->eventService->chits->eventIsInMinus($this->aid); // musi byt v before render aby se vyhodnotila az po handleru
        $this->template->list = $this->eventService->chits->getAll($aid);
        $this->template->linkImportHPD = $this->link("importHpd", ["aid" => $aid]);
        $this->fillTemplateVariables();
        if ($this->isAjax()) {
            $this->invalidateControl("contentSnip");
        }
    }

    public function actionImportHpd($aid) : void
    {
        $this->editableOnly();
        $totalPayment = $this->eventService->participants->getTotalPayment($this->aid);
        $func = $this->eventService->event->getFunctions($this->aid);
        $date = $this->eventService->event->get($aid)->StartDate;
        $hospodar = ($func[2]->ID_Person != NULL) ? $func[2]->Person : ""; //$func[0]->Person
        $category = $this->eventService->chits->getParticipantIncomeCategory();

        $values = ["date" => $date, "recipient" => $hospodar, "purpose" => "účastnické příspěvky", "price" => $totalPayment, "category" => $category];
        $add = $this->eventService->chits->add($this->aid, $values);
        if ($add) {
            $this->flashMessage("Účastníci byli importováni");
        } else {
            $this->flashMessage("Účastníky se nepodařilo importovat", "danger");
        }
        $this->redirect("default", ["aid" => $aid]);
    }

}
