<?php

declare(strict_types=1);

namespace App\Presentation\Payments\EducationAddParticipants;

use App\Components\Factories\Payment\IMassAddFormFactory;
use App\Components\Payment\MassAddForm;
use App\Model\Cashbook\ReadModel\Queries\EducationParticipantListQuery;
use App\Model\DTO\Participant\Participant;
use App\Model\Event\SkautisEducationId;
use App\Model\Payment\PaymentService;
use App\Model\Payment\ReadModel\Queries\MemberEmailsQuery;
use App\Model\Unit\ReadModel\Queries\UnitQuery;
use App\Presentation\Payments\PaymentsBasePresenter as BasePresenter;
use LogicException;

use function array_filter;
use function in_array;

final class EducationAddParticipantsPresenter extends BasePresenter
{
    private int $id;

    public function __construct(private PaymentService $model, private IMassAddFormFactory $formFactory)
    {
        parent::__construct();
    }

    /** @param null $unitId - NEZBYTNÝ PRO FUNKCI VÝBĚRU JINÉ JEDNOTKY */
    public function actionDefault(int $id, ?int $unitId = null): void
    {
        $this->id = $id;
        $group = $this->model->getGroup($id);

        if ($group === null || ! $this->isEditable) {
            $this->flashMessage('Neoprávněný přístup ke skupině.', 'danger');
            $this->redirect(':Payments:GroupList:');
        }

        if ($group->getSkautisId() === null) {
            $this->flashMessage('Neplatné propojení skupiny plateb se vzdělávací akcí.', 'warning');
            $this->redirect(':Payments:Dashboard:');
        }

        $participants = $this->queryBus->handle(
            new EducationParticipantListQuery(
                new SkautisEducationId($group->getSkautisId()),
            ),
        );

        $form = $this['massAddForm'];
        if (! $form instanceof MassAddForm) {
            throw new LogicException('Assertion failed.');
        }
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
                $amount === 0.0 ? null : $amount,
            );
        }

        $this->template->setParameters([
            'unit' => $this->queryBus->handle(new UnitQuery($this->getCurrentUnitId()->toInt())),
            'group' => $group,
            'showForm' => ! empty($participants),
        ]);
    }

    protected function createComponentMassAddForm(): MassAddForm
    {
        return $this->formFactory->create($this->id);
    }
}
