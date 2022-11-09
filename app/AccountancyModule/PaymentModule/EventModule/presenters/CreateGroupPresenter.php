<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\EventModule;

use App\AccountancyModule\PaymentModule\BasePresenter;
use App\AccountancyModule\PaymentModule\Components\GroupForm;
use App\AccountancyModule\PaymentModule\Factories\IGroupFormFactory;
use Cake\Chronos\Date;
use Model\Event\Event;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\ReadModel\Queries\EventsWithoutGroupQuery;

final class CreateGroupPresenter extends BasePresenter
{
    private IGroupFormFactory $formFactory;

    private Event $event;

    public function __construct(IGroupFormFactory $formFactory)
    {
        parent::__construct();
        $this->formFactory = $formFactory;
    }

    public function actionDefault(int $eventId): void
    {
        $eventsWithoutGroup = $this->queryBus->handle(new EventsWithoutGroupQuery(Date::today()->year));

        if (! $this->isEditable || ! isset($eventsWithoutGroup[$eventId])) {
            $this->flashMessage('Pro tuto akci není možné vytvořit skupinu plateb', 'danger');
            $this->redirect('Event:SelectForGroup:');
        }

        $this->event = $eventsWithoutGroup[$eventId];
        $this->template->setParameters(['event' => $this->event]);
    }

    protected function createComponentForm(): GroupForm
    {
        $form = $this->formFactory->create(
            $this->getCurrentUnitId(),
            SkautisEntity::fromEventId($this->event->getId()),
        );

        $form->fillName($this->event->getDisplayName());

        return $form;
    }
}
