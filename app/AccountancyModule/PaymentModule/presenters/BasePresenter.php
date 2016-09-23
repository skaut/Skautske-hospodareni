<?php

namespace App\AccountancyModule\PaymentModule;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BasePresenter extends \App\AccountancyModule\BasePresenter {

    /** @persistent */
    public $aid;
    protected $isReadable;

    /**
     *
     * @var \Model\PaymentService
     */
    protected $model;

    public function __construct(\Model\PaymentService $paymentService) {
        parent::__construct();
        $this->model = $paymentService;
    }

    protected function startup() {
        parent::startup();
        $this->availableActions = $this->userService->actionVerify("OU_Unit", $this->aid);
        $this->template->aid = $this->aid = (is_null($this->aid) ? $this->unitService->getUnitId() : $this->aid);
        $this->template->isReadable = $this->isReadable = key_exists($this->aid, $this->user->getIdentity()->access['read']);
        $this->template->isEditable = $this->isEditable = key_exists($this->aid, $this->user->getIdentity()->access['edit']);
        if (!$this->isReadable) {
            $this->flashMessage("Nemáte oprávnění pro zobrazení stránky", "warning");
            $this->redirect(":Accountancy:Default:", array("aid" => NULL));
        }
    }

    /**
     * 
     * @param string $v
     * @return bool
     */
    protected function noEmpty($v) {
        return $v == "" ? NULL : $v;
    }

}
