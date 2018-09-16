<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\MassAddForm;
use App\AccountancyModule\PaymentModule\Factories\IMassAddFormFactory;
use Model\EventEntity;
use Model\PaymentService;
use function array_filter;
use function in_array;

class CampPresenter extends BasePresenter
{
    /** @var string[] */
    protected $readUnits;

    /** @var PaymentService */
    private $model;

    /** @var EventEntity */
    protected $campService;

    /** @var IMassAddFormFactory */
    private $massAddFormFactory;

    /** @var int */
    private $id;

    public function __construct(PaymentService $model, IMassAddFormFactory $massAddFormFactory)
    {
        parent::__construct();
        $this->model              = $model;
        $this->massAddFormFactory = $massAddFormFactory;
    }

    protected function startup() : void
    {
        parent::startup();
        $this->template->unitPairs = $this->readUnits = $units = $this->unitService->getReadUnits($this->user);
        $this->campService         = $this->context->getService('campService');
    }

    public function actionMassAdd(int $id) : void
    {
        $this->id = $id;
        $group    = $this->model->getGroup($id);

        if ($group === null || ! $this->isEditable) {
            $this->flashMessage('Neoprávněný přístup ke skupině.', 'danger');
            $this->redirect('Payment:default');
        }

        if ($group->getSkautisId() === null) {
            $this->flashMessage('Neplatné propojení skupiny plateb s táborem.', 'warning');
            $this->redirect('Default:');
        }

        $participants = $this->campService->getParticipants()->getAll($group->getSkautisId());

        $form = $this['massAddForm'];
        /** @var MassAddForm $form */

        $personsWithPayment = $this->model->getPersonsWithActivePayment($id);

        $participants = array_filter(
            $participants,
            function ($p) use ($personsWithPayment) {
                return ! in_array($p->ID_Person, $personsWithPayment, true);
            }
        );

        foreach ($participants as $p) {
            $form->addPerson(
                $p->ID_Person,
                $this->model->getPersonEmails($p->ID_Person),
                $p->Person,
                $p->payment === 0 ? null : (float) $p->payment
            );
        }

        $this->template->id       = $id;
        $this->template->showForm = ! empty($participants);
    }

    protected function createComponentMassAddForm() : MassAddForm
    {
        return $this->massAddFormFactory->create($this->id);
    }
}
