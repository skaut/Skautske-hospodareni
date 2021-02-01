<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\EventModule;

use App\AccountancyModule\PaymentModule\BasePresenter;
use App\AccountancyModule\PaymentModule\Components\MassAddForm;
use App\AccountancyModule\PaymentModule\Factories\IMassAddFormFactory;
use Model\DTO\Participant\Participant;
use Model\Payment\ReadModel\Queries\EventParticipantsWithoutPaymentQuery;
use Model\Payment\ReadModel\Queries\MemberEmailsQuery;
use Model\PaymentService;

use function assert;

final class AddParticipantsPresenter extends BasePresenter
{
    private PaymentService $model;
    private IMassAddFormFactory $formFactory;

    private int $groupId;

    /** @var Participant[] */
    private array $participants;

    public function __construct(PaymentService $model, IMassAddFormFactory $formFactory)
    {
        parent::__construct();
        $this->model       = $model;
        $this->formFactory = $formFactory;
    }

    /**
     * @param int $id ID of payment group
     */
    public function actionDefault(int $id): void
    {
        $group = $this->model->getGroup($id);

        if ($group === null || ! $this->isEditable) {
            $this->flashMessage('Neoprávněný přístup ke skupině.', 'danger');
            $this->redirect(':Accountancy:Payment:GroupList:');
        }

        $this->groupId      = $id;
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
            assert($participant instanceof Participant);

            $amount = $participant->getPayment();
            $form->addPerson(
                $participant->getPersonId(),
                $this->queryBus->handle(new MemberEmailsQuery($participant->getPersonId())),
                $participant->getDisplayName(),
                $amount === 0.0 ? null : $amount
            );
        }

        return $form;
    }
}
