<?php

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\MassAddForm;
use App\AccountancyModule\PaymentModule\Factories\IMassAddFormFactory;
use Model\PaymentService;

class RegistrationPresenter extends BasePresenter
{

    protected $readUnits;

    /** @var PaymentService */
    private $model;

    /** @var IMassAddFormFactory */
    private $massAddFormFactory;

    /** @var int */
    private $id;

    private const STS_PRICE = 200;

    public function __construct(IMassAddFormFactory $massAddFormFactory, PaymentService $model)
    {
        parent::__construct();
        $this->model = $model;
        $this->massAddFormFactory = $massAddFormFactory;
    }


    protected function startup() : void
    {
        parent::startup();
        $this->template->unitPairs = $this->readUnits = $units = $this->unitService->getReadUnits($this->user);
    }

    public function actionMassAdd(int $id) : void
    {
        $this->id = $id;

        //ověření přístupu
        try {
            $list = $this->model->getPersonsFromRegistrationWithoutPayment(array_keys($this->readUnits), $id);
        } catch (\InvalidArgumentException $exc) {
            $this->flashMessage("Neoprávněný přístup ke skupině.", "danger");
            $this->redirect("Payment:default");
            return;
        }

        $group = $this->model->getGroup($id);

        if($group === NULL) {
            $this->flashMessage("Neplatný požadavek na přidání registračních plateb", "danger");
            $this->redirect("Payment:default");
        }

        $form = $this["massAddForm"];
        /* @var $form MassAddForm */

        foreach ($list as $p) {
            $stsCount = intdiv((int)$p['AmountServices'], self::STS_PRICE);

            $form->addPerson(
                $p["ID_Person"],
                $p["emails"],
                $p["Person"],
                (float)$p["AmountTotal"],
                $stsCount !== 0 ? "{$stsCount}x STS" : ""
            );
        }

        $this->template->id = $id;
        $this->template->showForm = !empty($list);
    }

    protected function createComponentMassAddForm()
    {
        return $this->massAddFormFactory->create($this->id);
    }

}
