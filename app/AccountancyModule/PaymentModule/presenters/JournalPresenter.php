<?php

namespace App\AccountancyModule\PaymentModule;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class JournalPresenter extends BasePresenter {
    
    public function __construct(\Model\PaymentService $paymentService) {
        parent::__construct($paymentService);
    }

    public function renderDefault($aid, $year = NULL) {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění přistupovat ke správě emailů", "danger");
            $this->redirect("Payment:default");
        }
        
        if(is_null($year)) {
            $year = date("Y");
        }
        $this->template->year = $year;
        
        $this->template->units = $units = $this->unitService->getAllUnder($this->aid);
        
        $changes = [];
        foreach (array_keys($units) as $unitId) {
            $changes[$unitId] = $this->model->getJournalChangesAfterRegistration($unitId, $year);
        }
        $this->template->changes = $changes;
    }

}
