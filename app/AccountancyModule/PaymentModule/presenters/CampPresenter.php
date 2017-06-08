<?php

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\MassAddForm;
use App\AccountancyModule\PaymentModule\Factories\IMassAddFormFactory;
use Model\DTO\Payment\Payment;
use Model\PaymentService;

class CampPresenter extends BasePresenter
{

    protected $readUnits;

    /** @var \Model\EventEntity */
    protected $campService;

    /** @var IMassAddFormFactory */
    private $massAddFormFactory;

    /** @var int */
    private $id;

    public function __construct(PaymentService $paymentService, IMassAddFormFactory $massAddFormFactory)
    {
        parent::__construct($paymentService);
        $this->massAddFormFactory = $massAddFormFactory;
    }

    protected function startup() : void
    {
        parent::startup();
        $this->template->unitPairs = $this->readUnits = $units = $this->unitService->getReadUnits($this->user);
        $this->campService = $this->context->getService("campService");
    }

    public function actionMassAdd(int $id) : void
    {
        $this->id = $id;
        $group = $this->model->getGroup($id);

        if($group === NULL || !$this->isEditable) {
            $this->flashMessage("Neoprávněný přístup ke skupině.", "danger");
            $this->redirect("Payment:default");
        }

        if ($group->getSkautisId() === NULL) {
            $this->flashMessage("Neplatné propojení skupiny plateb s táborem.", "warning");
            $this->redirect("Default:");
        }

        $participants = $this->campService->participants->getAll($group->getSkautisId());

        $form = $this['massAddForm'];
        /* @var $form MassAddForm */

        $personsWithPayment = $this->model->getPersonsWithActivePayment($id);

        $participants = array_filter($participants, function($p) use($personsWithPayment) {
            return !in_array($p->ID_Person, $personsWithPayment, TRUE);
        });

        foreach ($participants as $p) {
            $form->addPerson(
                $p->ID_Person,
                $this->model->getPersonEmails($p->ID_Person),
                $p->Person,
                $p->payment === 0 ? NULL : (float)$p->payment
            );
        }

        $this->template->id = $id;
        $this->template->showForm = !empty($participants);
    }

    protected function createComponentMassAddForm(): MassAddForm
    {
        return $this->massAddFormFactory->create($this->id);
    }

}
