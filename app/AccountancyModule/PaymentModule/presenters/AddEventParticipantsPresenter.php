<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\MassAddForm;
use App\AccountancyModule\PaymentModule\Factories\IMassAddFormFactory;
use Model\DTO\Participant\Participant;
use Model\Payment\ReadModel\Queries\EventParticipantsWithoutPaymentQuery;
use Model\Payment\ReadModel\Queries\MemberEmailsQuery;
use Model\PaymentService;

use function assert;
use function count;

final class AddEventParticipantsPresenter extends BasePresenter
{
    private PaymentService $model;
    private IMassAddFormFactory $formFactory;

    private int $groupId;

    public function __construct(PaymentService $model, IMassAddFormFactory $formFactory)
    {
        parent::__construct();
        $this->model       = $model;
        $this->formFactory = $formFactory;
    }

    /**
     * @param int $id ID of payment group
     */
    public function actionMassAdd(int $id): void
    {
        $group = $this->model->getGroup($id);

        if ($group === null || ! $this->isEditable) {
            $this->flashMessage('Neoprávněný přístup ke skupině.', 'danger');
            $this->redirect('GroupList:');
        }

        $this->groupId = $id;

        $participants = $this->queryBus->handle(new EventParticipantsWithoutPaymentQuery($id));

        $form = $this['massAddForm'];
        assert($form instanceof MassAddForm);

        foreach ($participants as $participant) {
            assert($participant instanceof Participant);

            $amount = $participant->getPayment();
            $form->addPerson(
                $participant->getPersonId(),
                $this->queryBus->handle(new MemberEmailsQuery($participant->getPersonId())),
                $participant->getDisplayName(),
                $amount === 0.0 ? null : $amount
            );
        }

        $this->template->setParameters([
            'group' => $group,
            'showForm' => count($participants) !== 0,
        ]);
    }

    protected function createComponentMassAddForm(): MassAddForm
    {
        return $this->formFactory->create($this->groupId);
    }
}
