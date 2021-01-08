<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\MassAddForm;
use App\AccountancyModule\PaymentModule\Factories\IMassAddFormFactory;
use Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use Model\DTO\Participant\Participant;
use Model\DTO\Payment\Group;
use Model\Event\SkautisCampId;
use Model\Payment\ReadModel\Queries\MemberEmailsQuery;
use Model\PaymentService;
use Model\Unit\ReadModel\Queries\UnitQuery;

use function array_filter;
use function in_array;

final class AddCampParticipantsPresenter extends BasePresenter
{
    private PaymentService $model;
    private IMassAddFormFactory $formFactory;

    private Group $group;

    /** @var Participant[] */
    private array $participants;

    public function __construct(PaymentService $model, IMassAddFormFactory $formFactory)
    {
        parent::__construct();
        $this->model       = $model;
        $this->formFactory = $formFactory;
    }

    public function actionDefault(int $id): void
    {
        $group = $this->model->getGroup($id);

        if ($group === null || ! $this->isEditable) {
            $this->flashMessage('Neoprávněný přístup ke skupině.', 'danger');
            $this->redirect('GroupList:');
        }

        if ($group->getSkautisId() === null) {
            $this->flashMessage('Neplatné propojení skupiny plateb s táborem.', 'warning');
            $this->redirect('Default:');
        }

        $this->group        = $group;
        $this->participants = $this->queryBus->handle(
            new CampParticipantListQuery(new SkautisCampId($group->getSkautisId()))
        );

        $this->template->setParameters([
            'unit' => $this->queryBus->handle(new UnitQuery($this->getCurrentUnitId()->toInt())),
            'group' => $group,
            'showForm' => $this->participants !== [],
        ]);
    }

    protected function createComponentMassAddForm(): MassAddForm
    {
        $form = $this->formFactory->create($this->group->getId());

        $personsWithPayment = $this->model->getPersonsWithActivePayment($this->group->getId());

        $participants = array_filter(
            $this->participants,
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

        return $form;
    }
}
