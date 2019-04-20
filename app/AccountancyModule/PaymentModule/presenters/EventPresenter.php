<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\GroupForm;
use App\AccountancyModule\PaymentModule\Factories\IGroupFormFactory;
use Assert\Assertion;
use Cake\Chronos\Date;
use Model\Common\UnitId;
use Model\Event\Event;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\Group\Type;
use Model\Payment\ReadModel\Queries\EventsWithoutGroupQuery;

final class EventPresenter extends BasePresenter
{
    /** @var Event|null */
    private $event;

    /** @var IGroupFormFactory */
    private $groupFormFactory;

    public function __construct(IGroupFormFactory $groupFormFactory)
    {
        parent::__construct();
        $this->groupFormFactory = $groupFormFactory;
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

    protected function createComponentNewGroupForm() : GroupForm
    {
        $event = $this->event;

        Assertion::notNull($event);

        $form = $this->groupFormFactory->create(
            new UnitId($this->getCurrentUnitId()),
            new SkautisEntity($event->getId()->toInt(), Type::get(Type::EVENT))
        );

        $form->fillName($event->getDisplayName());

        return $form;
    }

    /**
     * @return Event[]
     */
    private function getEventsWithoutGroup() : array
    {
        return $this->queryBus->handle(new EventsWithoutGroupQuery(Date::today()->year));
    }
}
