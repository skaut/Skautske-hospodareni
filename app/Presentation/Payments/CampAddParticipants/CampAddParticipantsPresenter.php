<?php

declare(strict_types=1);

namespace App\Presentation\Payments\CampAddParticipants;

use App\Components\Factories\Payment\IMassAddFormFactory;
use App\Components\Payment\MassAddForm;
use App\Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use App\Model\DTO\Participant\Participant;
use App\Model\DTO\Payment\Group;
use App\Model\Event\Exception\CampInvitationNotFound;
use App\Model\Event\SkautisCampId;
use App\Model\Payment\PaymentService;
use App\Model\Payment\ReadModel\Queries\MemberEmailsQuery;
use App\Model\Unit\ReadModel\Queries\UnitQuery;
use App\Model\Utils\MoneyFactory;
use App\Presentation\Payments\PaymentsBasePresenter as BasePresenter;
use LogicException;

use function array_filter;
use function in_array;

final class CampAddParticipantsPresenter extends BasePresenter
{
    private Group $group;

    public function __construct(private PaymentService $model, private IMassAddFormFactory $formFactory)
    {
        parent::__construct();
    }

    public function actionDefault(int $id): void
    {
        $group = $this->model->getGroup($id);

        if ($group === null || ! $this->isEditable) {
            $this->flashMessage('Neoprávněný přístup ke skupině.', 'danger');
            $this->redirect(':Payments:GroupList:');
        }

        if ($group->getSkautisId() === null) {
            $this->flashMessage('Neplatné propojení skupiny plateb s táborem.', 'warning');
            $this->redirect(':Payments:Dashboard:');
        }

        $this->group = $group;
        $participants = $this->queryBus->handle(
            new CampParticipantListQuery(new SkautisCampId($group->getSkautisId())),
        );

        $participantPaymentDetails = [];
        try {
            $participantPaymentDetails = $this->model->getParticipantPaymentDetails(new SkautisCampId((int) $this->group->getSkautisId()));
        } catch (CampInvitationNotFound) {
            $this->flashMessage('Nelze načíst data z e-přihlášek. E-přihláška není aktivní, nebo nemáte oprávnění.', 'warning');
        }

        $form = $this['massAddForm'];
        if (! $form instanceof MassAddForm) {
            throw new LogicException('Assertion failed.');
        }

        $personsWithPayment = $this->model->getPersonsWithActivePayment($this->group->getId());

        $participantsWithoutPayment = array_filter(
            $participants,
            fn (Participant $p) => ! in_array($p->getPersonId(), $personsWithPayment, true),
        );

        foreach ($participantsWithoutPayment as $p) {
            $participantPaymentDetail = $participantPaymentDetails[$p->getPersonId()] ?? null;
            if ($participantPaymentDetail) {
                $paymentNote = $participantPaymentDetail->getPaymentNote();
                $variableSymbol = $participantPaymentDetail->getVariableSymbol();
                $dueDate = $participantPaymentDetail->getPaymentTerm();
                $amount = $p->getPayment()->isZero() ? MoneyFactory::fromDecimal((string) $participantPaymentDetail->getPrice()) : $p->getPayment();
            } else {
                $paymentNote = '';
                $variableSymbol = '';
                $dueDate = null;
                $amount = $p->getPayment()->isZero() ? null : $p->getPayment();
            }

            $form->addPerson(
                $p->getPersonId(),
                $this->queryBus->handle(new MemberEmailsQuery($p->getPersonId())),
                $p->getDisplayName(),
                $amount === null || $amount->isZero() ? null : $amount,
                $paymentNote ?? '',
                $variableSymbol ?? '',
                $dueDate,
            );
        }

        $this->template->setParameters([
            'unit' => $this->queryBus->handle(new UnitQuery($this->getCurrentUnitId()->toInt())),
            'group' => $group,
            'showForm' => $participants !== [],
        ]);
    }

    protected function createComponentMassAddForm(): MassAddForm
    {
        return $this->formFactory->create($this->group->getId());
    }
}
