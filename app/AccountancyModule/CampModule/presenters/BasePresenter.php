<?php

namespace App\AccountancyModule\CampModule;

use Skautis\Wsdl\PermissionException;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BasePresenter extends \App\AccountancyModule\BasePresenter {

    const STable = "EV_EventCamp";

    protected $event;

    /**
     *
     * @var \Model\EventService
     */
    protected $eventService;

    protected function startup() {
        parent::startup();
        $this->eventService = $this->context->getService("campService");
        $this->isCamp = $this->template->isCamp = true;
        $this->template->aid = $this->aid = $this->getParameter("aid", NULL);

        if (isset($this->aid) && !is_null($this->aid)) {//pokud je nastavene ID akce tak zjištuje stav dané akce a kontroluje oprávnění
            try {
                $this->template->event = $this->event = $this->eventService->event->get($this->aid);
                $this->availableActions = $this->userService->actionVerify(self::STable, $this->aid);
                $this->template->isEditable = $this->isEditable = $this->isAllowed(self::STable . "_UPDATE_Real");
            } catch (PermissionException $exc) {
                $this->flashMessage($exc->getMessage(), "danger");
                $this->redirect("Default:");
            }
        } else {
            $this->availableActions = $this->userService->actionVerify(self::STable); //zjistení událostí nevázaných na konretnní
        }
    }

    protected function editableOnly() {
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
