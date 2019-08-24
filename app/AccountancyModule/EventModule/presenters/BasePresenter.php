<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule;

use Model\Auth\Resources\Event as ResourceEvent;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\DTO\Cashbook\Cashbook;
use Model\Event\Event;
use Model\Event\ReadModel\Queries\EventQuery;
use Model\Event\SkautisEventId;
use Model\EventEntity;
use Nette\Utils\ArrayHash;
use function assert;

class BasePresenter extends \App\AccountancyModule\BasePresenter
{
    /** @var Event */
    protected $event;

    /** @var EventEntity */
    protected $eventService;

    protected function startup() : void
    {
        parent::startup();
        $this->eventService = $this->context->getService('eventService');
        $this->type         = ObjectType::EVENT;
        $this->template->setParameters([
            'aid' => $this->aid,
        ]);

        //pokud je nastavene ID akce tak zjištuje stav dané akce a kontroluje oprávnění
        if ($this->aid === null) {
            return;
        }
        $cashbookId = $this->queryBus->handle(new EventCashbookIdQuery(new SkautisEventId($this->aid)));
        $cashbook   = $this->queryBus->handle(new CashbookQuery($cashbookId));
        assert($cashbook instanceof Cashbook);

        $this->event = $this->queryBus->handle(new EventQuery(new SkautisEventId($this->aid)));
        assert($this->event instanceof Event);
        $this->template->setParameters([
            'event' => $this->event,
            'isEditable'=> $this->isEditable = $this->authorizator->isAllowed(ResourceEvent::UPDATE, $this->aid),
            'prefix' => $cashbook->getChitNumberPrefix(),
        ]);
    }

    protected function editableOnly() : void
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
