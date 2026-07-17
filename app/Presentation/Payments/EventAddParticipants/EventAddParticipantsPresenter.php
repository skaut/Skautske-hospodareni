<?php

declare(strict_types=1);

namespace App\Presentation\Payments\EventAddParticipants;

use App\Components\Factories\Payment\IMassAddFormFactory;
use App\Components\Payment\MassAddForm;
use App\Model\DTO\Participant\Participant;
use App\Model\Payment\PaymentService;
use App\Model\Payment\ReadModel\Queries\EventParticipantsWithoutPaymentQuery;
use App\Model\Payment\ReadModel\Queries\MemberEmailsQuery;
use App\Presentation\Payments\PaymentsBasePresenter as BasePresenter;
use LogicException;

final class EventAddParticipantsPresenter extends BasePresenter
{
    private int $groupId;

    /** @var Participant[] */
    private array $participants;

    public function __construct(private PaymentService $model, private IMassAddFormFactory $formFactory)
    {
        parent::__construct();
    }

    /** @param int $id ID of payment group */
    public function actionDefault(int $id): void
    {
        $group = $this->model->getGroup($id);

        if ($group === null || ! $this->isEditable) {
            $this->flashMessage('Neoprávněný přístup ke skupině.', 'danger');
            $this->redirect(':Payments:GroupList:');
        }

        $this->groupId = $id;
        $this->participants = $this->queryBus->handle(new EventParticipantsWithoutPaymentQuery($id));

        $this->template->setParameters([
            'group' => $group,
            'showForm' => $this->participants !== [],
        ]);
    }

    protected function createComponentMassAddForm(): MassAddForm
    {
        $form = $this->formFactory->create($this->groupId);

        foreach ($this->participants as $participant) {
            if (! $participant instanceof Participant) {
                throw new LogicException('Assertion failed.');
            }
            $amount = $participant->getPayment();
            $form->addPerson(
                $participant->getPersonId(),
                $this->queryBus->handle(new MemberEmailsQuery($participant->getPersonId())),
                $participant->getDisplayName(),
                $amount === 0.0 ? null : $amount,
            );
        }

        return $form;
    }
}
