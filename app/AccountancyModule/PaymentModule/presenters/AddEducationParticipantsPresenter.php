<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\MassAddForm;
use App\AccountancyModule\PaymentModule\Factories\IMassAddFormFactory;
use Model\Cashbook\ReadModel\Queries\EducationParticipantListQuery;
use Model\DTO\Participant\Participant;
use Model\Event\SkautisEducationId;
use Model\Payment\ReadModel\Queries\MemberEmailsQuery;
use Model\PaymentService;
use Model\Unit\ReadModel\Queries\UnitQuery;
use function array_filter;
use function assert;
use function in_array;

class AddEducationParticipantsPresenter extends BasePresenter
{
    private PaymentService $model;

    private IMassAddFormFactory $formFactory;

    private int $id;

    public function __construct(PaymentService $model, IMassAddFormFactory $formFactory)
    {
        parent::__construct();
        $this->model       = $model;
        $this->formFactory = $formFactory;
    }

    /**
     * @param null $unitId - NEZBYTNÝ PRO FUNKCI VÝBĚRU JINÉ JEDNOTKY
     */
    public function actionDefault(int $id, ?int $unitId = null) : void
    {
        $this->id = $id;
        $group    = $this->model->getGroup($id);

        if ($group === null || ! $this->isEditable) {
            $this->flashMessage('Neoprávněný přístup ke skupině.', 'danger');
            $this->redirect('GroupList:');
        }

        if ($group->getSkautisId() === null) {
            $this->flashMessage('Neplatné propojení skupiny plateb se vzdělávací akcí.', 'warning');
            $this->redirect('Default:');
        }

        $participants = $this->queryBus->handle(
            new EducationParticipantListQuery(
                new SkautisEducationId($group->getSkautisId())
            )
        );

        $form = $this['massAddForm'];
        assert($form instanceof MassAddForm);

        $personsWithPayment = $this->model->getPersonsWithActivePayment($id);

        $participants = array_filter(
            $participants,
            fn (Participant $p) => ! in_array($p->getPersonId(), $personsWithPayment, true),
        );

        foreach ($participants as $p) {
            $amount = $p->getPayment();
            $form->addPerson(
                $p->getPersonId(),
                $this->queryBus->handle(new MemberEmailsQuery($p->getPersonId())),
                $p->getDisplayName(),
                $amount === 0.0 ? null : $amount
            );
        }
        $this->template->setParameters([
            'unit' => $this->queryBus->handle(new UnitQuery($this->getCurrentUnitId()->toInt())),
            'group'    => $group,
            'showForm' => ! empty($participants),
        ]);
    }

    protected function createComponentMassAddForm() : MassAddForm
    {
        return $this->formFactory->create($this->id);
    }
}
