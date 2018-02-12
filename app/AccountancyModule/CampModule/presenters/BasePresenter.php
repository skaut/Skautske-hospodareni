<?php

namespace App\AccountancyModule\CampModule;

use Model\Auth\Resources\Camp;
use Model\Cashbook\ObjectType;
use Model\EventEntity;
use Skautis\Wsdl\PermissionException;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BasePresenter extends \App\AccountancyModule\BasePresenter
{

    const STable = "EV_EventCamp";

    /** @var \stdClass */
    protected $event;

    /** @var EventEntity */
    protected $eventService;

    protected function startup() : void
    {
        parent::startup();
        $this->eventService = $this->context->getService("campService");
        $this->type = ObjectType::CAMP;
        $this->template->aid = $this->aid;

        if ($this->aid !== NULL) {
            $this->template->event = $this->event = $this->eventService->event->get($this->aid);
            $this->template->isEditable = $this->isEditable = $this->authorizator->isAllowed(Camp::UPDATE_REAL, $this->aid);
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
