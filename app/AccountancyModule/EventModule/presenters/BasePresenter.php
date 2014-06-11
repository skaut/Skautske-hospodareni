<?php

namespace App\AccountancyModule\EventModule;

use Nette\Application\Routers\Route,
    Nette\Application\Routers\RouteList,
    Sinacek\MyRoute;

/**
 * @author sinacek
 */
class BasePresenter extends \App\AccountancyModule\BasePresenter {

    const STable = "EV_EventGeneral";

    protected $event;

    protected function startup() {
        parent::startup();
        $this->isCamp = $this->template->isCamp = false;
        $this->template->aid = $this->aid = $this->getParameter("aid", NULL);

        if (isset($this->aid) && !is_null($this->aid)) {//pokud je nastavene ID akce tak zjištuje stav dané akce a kontroluje oprávnění
            try {
                $this->template->event = $this->event = $this->context->eventService->event->get($this->aid);
                $this->availableActions = $this->context->userService->actionVerify(self::STable, $this->aid);
                $this->template->isEditable = $this->isEditable = array_key_exists("EV_EventGeneral_UPDATE", $this->availableActions);
            } catch (\SkautIS\Exception\PermissionException $exc) {
                $this->flashMessage($exc->getMessage(), "danger");
                $this->redirect("Event:");
            }
        } else {
            $this->availableActions = $this->context->userService->actionVerify(self::STable); //zjistení událostí nevázaných na konretnní akci
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

    /**
     * vytváří routy pro modul
     * @param RouteList $router
     * @param string $prefix 
     */
    static function createRoutes($prefix = "") {
        $router = new RouteList("Event");

        $prefix .= "akce/";

        $router[] = new MyRoute($prefix . '<aid [0-9]+>/<presenter>/[<action>/]', array(
            'presenter' => array(
                Route::VALUE => 'Event',
                Route::FILTER_TABLE => array(
                    'ucastnici' => 'Participant',
                    'kniha' => 'Cashbook',
                )),
            'action' => "default",
                ), Route::SECURED);

        $router[] = new MyRoute($prefix . '[<presenter>/][<action>/]', array(
            'presenter' => 'Default',
            'action' => 'default',
                ), Route::SECURED);
        return $router;
    }

}
