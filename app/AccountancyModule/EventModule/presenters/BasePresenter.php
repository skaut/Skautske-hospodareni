<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule;

use Model\Auth\Resources\Event as ResourceEvent;
use Model\Cashbook\ObjectType;
use Model\Event\Event;
use Model\Event\EventNotFound;
use Model\Event\ReadModel\Queries\EventQuery;
use Model\Event\SkautisEventId;

use function assert;

class BasePresenter extends \App\AccountancyModule\BasePresenter
{
    protected Event $event;

    protected function startup(): void
    {
        parent::startup();
        $this->type = ObjectType::EVENT;
        $this->template->setParameters([
            'aid' => $this->aid,
        ]);

        //pokud je nastavene ID akce tak zjištuje stav dané akce a kontroluje oprávnění
        if ($this->aid === null) {
            return;
        }

        try {
            $this->event = $this->queryBus->handle(new EventQuery(new SkautisEventId($this->aid)));
            assert($this->event instanceof Event);
        } catch (EventNotFound) {
            $this->template->setParameters(['message' => 'Nemáte oprávnění načíst akci nebo akce neexsituje.']);
            $this->forward('Default:accessDenied');
        }

        $this->template->setParameters([
            'event' => $this->event,
            'isEditable' => $this->isEditable = $this->authorizator->isAllowed(ResourceEvent::UPDATE, $this->aid),
        ]);
    }

    protected function editableOnly(): void
    {
        if ($this->isEditable) {
            return;
        }

        $this->flashMessage('Akce je uzavřena a nelze ji upravovat.', 'danger');
        if ($this->isAjax()) {
            $this->sendPayload();
        } else {
            $this->redirect('Default:');
        }
    }
}
