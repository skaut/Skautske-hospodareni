<?php

namespace App\AccountancyModule\EventModule;

use Model\EventEntity;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BasePresenter extends \App\AccountancyModule\BasePresenter
{

    const STable = "EV_EventGeneral";

    /** @var array */
    protected $event;

    /** @var EventEntity */
    protected $eventService;

    protected function startup() : void
    {
        parent::startup();
        $this->eventService = $this->context->getService("eventService");
        $this->isCamp = $this->template->isCamp = FALSE;
        $this->template->aid = $this->aid = $this->getParameter("aid", NULL);

        if (isset($this->aid) && !is_null($this->aid)) {//pokud je nastavene ID akce tak zjištuje stav dané akce a kontroluje oprávnění
            try {
                $this->template->event = $this->event = $this->eventService->event->get($this->aid);
                $this->availableActions = $this->userService->actionVerify(self::STable, $this->aid);
                $this->template->isEditable = $this->isEditable = array_key_exists("EV_EventGeneral_UPDATE", $this->availableActions);
            } catch (\Skautis\Wsdl\PermissionException $exc) {
                $this->flashMessage($exc->getMessage(), "danger");
                $this->redirect("Event:");
            }
        } else {
            $this->availableActions = $this->userService->actionVerify(self::STable); //zjistení událostí nevázaných na konretnní akci
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
