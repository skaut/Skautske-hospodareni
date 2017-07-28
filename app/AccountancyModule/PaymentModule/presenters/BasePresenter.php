<?php

namespace App\AccountancyModule\PaymentModule;
use Model\PaymentService;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BasePresenter extends \App\AccountancyModule\BasePresenter
{

    /** @persistent */
    public $aid;

    /**
     *
     * @var PaymentService
     */
    protected $model;

    /** @var int[] */
    private $editableUnits;

    public function __construct(PaymentService $paymentService)
    {
        parent::__construct();
        $this->model = $paymentService;
    }

    protected function startup(): void
    {
        parent::startup();

        $this->aid = $this->aid ?? $this->unitService->getUnitId();
        $this->availableActions = $this->userService->actionVerify("OU_Unit", $this->aid);

        $user = $this->getUser();
        $readableUnits = $this->unitService->getReadUnits($user);

        $isReadable = isset($readableUnits[$this->aid]);

        $this->editableUnits = array_keys($this->unitService->getEditUnits($this->getUser()));
        $this->isEditable = in_array($this->aid, $this->editableUnits);

        if (!$isReadable) {
            $this->flashMessage("Nemáte oprávnění pro zobrazení stránky", "warning");
            $this->redirect(":Accountancy:Default:", ["aid" => NULL]);
        }
    }


    protected function beforeRender(): void
    {
        parent::beforeRender();
        $this->template->aid = $this->aid;
        $this->template->isEditable = $this->isEditable;
    }


    /**
     *
     * @param string $v
     * @return bool
     */
    protected function noEmpty($v)
    {
        return $v == "" ? NULL : $v;
    }

    /**
     * @return int[]
     */
    protected function getEditableUnits(): array
    {
        return $this->editableUnits;
    }

}
