<?php

namespace App\AccountancyModule\PaymentModule;

use Model\PaymentService;

class JournalPresenter extends BasePresenter
{

    /** @var PaymentService */
    private $model;

    public function __construct(PaymentService $model)
    {
        parent::__construct();
        $this->model = $model;
    }


    public function renderDefault(int $aid, $year = NULL): void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění přistupovat ke správě emailů", "danger");
            $this->redirect("Payment:default");
        }

        if (is_null($year)) {
            $year = date("Y");
        }
        $this->template->year = $year;

        $this->template->units = $units = $this->unitService->getAllUnder($this->aid);

        $changes = [];
        $changeExists = FALSE;
        foreach (array_keys($units) as $unitId) {
            $uch = $this->model->getJournalChangesAfterRegistration($unitId, $year);
            $changeExists = $changeExists || (empty($uch["add"]) && $uch["remove"]);
            $changes[$unitId] = $uch;
        }
        $this->template->changes = $changes;
        $this->template->changeExists = $changeExists;
    }

}
