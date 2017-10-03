<?php

namespace App\AccountancyModule\EventModule;

use Model\ChitService;
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
        $this->type = ChitService::EVENT_TYPE_GENERAL;
        $this->template->aid = $this->aid = $this->getParameter("aid", NULL);

        $availableActions = [];
        if (isset($this->aid) && !is_null($this->aid)) {//pokud je nastavene ID akce tak zjištuje stav dané akce a kontroluje oprávnění
            try {
                $this->template->event = $this->event = $this->eventService->event->get($this->aid);
                $availableActions = $this->userService->getAvailableActions(self::STable, $this->aid);
                $this->template->isEditable = $this->isEditable = array_key_exists("EV_EventGeneral_UPDATE", $this->availableActions);
            } catch (\Skautis\Wsdl\PermissionException $exc) {
                $this->flashMessage($exc->getMessage(), "danger");
                $this->redirect("Default:");
            }
        } else {
            $availableActions = $this->userService->getAvailableActions(self::STable); //zjistení událostí nevázaných na konretnní akci
        }

        $this->availableActions = array_fill_keys($availableActions, TRUE);
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
