<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\GroupForm;
use App\AccountancyModule\PaymentModule\Components\MassAddForm;
use App\AccountancyModule\PaymentModule\Factories\IGroupFormFactory;
use App\AccountancyModule\PaymentModule\Factories\IMassAddFormFactory;
use Assert\Assertion;
use Cake\Chronos\Date;
use Model\DTO\Participant\Participant;
use Model\Event\Event;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\ReadModel\Queries\EventParticipantsWithoutPaymentQuery;
use Model\Payment\ReadModel\Queries\EventsWithoutGroupQuery;
use Model\Payment\ReadModel\Queries\MemberEmailsQuery;
use Model\PaymentService;
use function assert;
use function count;

final class EventPresenter extends BasePresenter
{
    /** @var Event|null */
    private $event;

    /** @var int|null */
    private $groupId;

    /** @var IGroupFormFactory */
    private $groupFormFactory;

    /** @var IMassAddFormFactory */
    private $massAddFormFactory;

    /** @var PaymentService */
    private $model;

    public function __construct(
        IGroupFormFactory $groupFormFactory,
        Factories\IMassAddFormFactory $massAddFormFactory,
        PaymentService $model
    ) {
        parent::__construct();
        $this->groupFormFactory   = $groupFormFactory;
        $this->massAddFormFactory = $massAddFormFactory;
        $this->model              = $model;
    }

    public function actionDefault() : void
    {
        $this->template->setParameters(['events' => $this->getEventsWithoutGroup()]);
    }

    public function actionNewGroup(int $eventId) : void
    {
        $eventsWithoutGroup = $this->getEventsWithoutGroup();

        if (! $this->isEditable || ! isset($eventsWithoutGroup[$eventId])) {
            $this->flashMessage('Pro tuto akci není možné vytvořit skupinu plateb', 'danger');
            $this->redirect('default');
        }

        $this->event = $eventsWithoutGroup[$eventId];
        $this->template->setParameters(['event' => $this->event]);
    }

    /**
     * @param int $id ID of payment group
     */
    public function actionMassAdd(int $id) : void
    {
        $group = $this->model->getGroup($id);

        if ($group === null || ! $this->isEditable) {
            $this->flashMessage('Neoprávněný přístup ke skupině.', 'danger');
            $this->redirect('Payment:default');
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

    protected function createComponentNewGroupForm() : GroupForm
    {
        $event = $this->event;

        Assertion::notNull($event);
        $unitId = $this->getCurrentUnitId();
        Assertion::notNull($unitId);

        $form = $this->groupFormFactory->create(
            $unitId,
            SkautisEntity::fromEventId($event->getId())
        );

        $form->fillName($event->getDisplayName());

        return $form;
    }

    protected function createComponentMassAddForm() : MassAddForm
    {
        $groupId = $this->groupId;

        Assertion::notNull($groupId);

        return $this->massAddFormFactory->create($groupId);
    }

    /**
     * @return Event[]
     */
    private function getEventsWithoutGroup() : array
    {
        return $this->queryBus->handle(new EventsWithoutGroupQuery(Date::today()->year));
    }
}
