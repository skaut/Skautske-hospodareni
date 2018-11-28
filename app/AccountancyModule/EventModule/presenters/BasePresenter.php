<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule;

use Model\Auth\Resources\Event;
use Model\Cashbook\ObjectType;
use Model\EventEntity;
use Skautis\Wsdl\PermissionException;

class BasePresenter extends \App\AccountancyModule\BasePresenter
{
    /** @var \stdClass */
    protected $event;

    /** @var EventEntity */
    protected $eventService;

    protected function startup() : void
    {
        parent::startup();

        $this->eventService  = $this->context->getService('eventService');
        $this->type          = ObjectType::EVENT;
        $this->template->aid = $this->aid;

        //pokud je nastavene ID akce tak zjištuje stav dané akce a kontroluje oprávnění
        if ($this->aid === null) {
            return;
        }

        try {
            $this->template->event = $this->event = $this->eventService->getEvent()->get($this->aid);
        } catch (PermissionException $exc) {
            $this->flashMessage('Nemáte oprávnění pro načtení dat z akce', 'warning');
            $this->redirect(':Accountancy:Default:', ['aid' => null]);
        }

        $this->template->isEditable = $this->isEditable = $this->authorizator->isAllowed(Event::UPDATE, $this->aid);
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
