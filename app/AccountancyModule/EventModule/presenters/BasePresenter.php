<?php

namespace App\AccountancyModule\EventModule;

use Model\Auth\Resources\Event;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Event\SkautisEventId;
use Model\EventEntity;

class BasePresenter extends \App\AccountancyModule\BasePresenter
{

    const STable = "EV_EventGeneral";

    /** @var \stdClass */
    protected $event;

    /** @var EventEntity */
    protected $eventService;

    protected function startup() : void
    {
        parent::startup();
        $this->eventService = $this->context->getService("eventService");
        $this->type = ObjectType::EVENT;
        $this->template->aid = $this->aid = $this->getParameter("aid", NULL);

        //pokud je nastavene ID akce tak zjištuje stav dané akce a kontroluje oprávnění
        if ($this->aid !== NULL) {
            $this->template->event = $this->event = $this->eventService->event->get($this->aid);
            $this->template->isEditable = $this->isEditable = $this->authorizator->isAllowed(Event::UPDATE, $this->aid);
        }

    }

    protected function editableOnly() : void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Akce je uzavřena a nelze ji upravovat.", "danger");
            if ($this->isAjax()) {
                $this->sendPayload();
            } else {
                $this->redirect("Default:");
            }
        }
    }

}
