<?php

declare(strict_types=1);

namespace App\Presentation\Payments\EventCreateGroup;

use App\Components\Factories\Payment\IGroupFormFactory;
use App\Components\Payment\GroupForm;
use App\Model\Event\Event;
use App\Model\Payment\Group\SkautisEntity;
use App\Model\Payment\ReadModel\Queries\EventsWithoutGroupQuery;
use App\Presentation\Payments\PaymentsBasePresenter as BasePresenter;
use Cake\Chronos\ChronosDate;
use LogicException;

final class EventCreateGroupPresenter extends BasePresenter
{
    private Event $event;

    public function __construct(private IGroupFormFactory $formFactory)
    {
        parent::__construct();
    }

    public function actionDefault(int $eventId): void
    {
        $eventsWithoutGroup = $this->queryBus->handle(new EventsWithoutGroupQuery(ChronosDate::today()->year));

        if (! $this->isEditable || ! isset($eventsWithoutGroup[$eventId])) {
            $this->flashMessage('Pro tuto akci není možné vytvořit skupinu plateb', 'danger');
            $this->redirect(':Payments:EventSelectForGroup:');
        }

        $this->event = $eventsWithoutGroup[$eventId];
        $this->template->setParameters(['event' => $this->event]);

        $form = $this['form'];
        if (! $form instanceof GroupForm) {
            throw new LogicException('Assertion failed.');
        }
        $form->fillName($this->event->getDisplayName());
    }

    protected function createComponentForm(): GroupForm
    {
        return $this->formFactory->create(
            $this->getCurrentUnitId(),
            SkautisEntity::fromEventId($this->event->getId()),
        );
    }
}
