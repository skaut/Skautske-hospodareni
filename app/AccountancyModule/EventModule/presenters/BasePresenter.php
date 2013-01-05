<?php

/**
 * @author sinacek
 */
class Accountancy_Event_BasePresenter extends Accountancy_BasePresenter {

    const STable = "EV_EventGeneral";
    
    protected $event;

    protected function startup() {
        parent::startup();

        $this->template->aid = $this->aid = $this->getParameter("aid", NULL);

        if (isset($this->aid) && !is_null($this->aid)) {//pokud je nastavene ID akce tak zjištuje stav dané akce a kontroluje oprávnění
            try {
                $this->template->event = $this->event = $this->context->eventService->event->get($this->aid);
                $this->availableActions = $this->context->userService->actionVerify(self::STable, $this->aid);
                $this->template->isEditable = $this->isEditable = array_key_exists("EV_EventGeneral_UPDATE", $this->availableActions);
            } catch (SkautIS_PermissionException $exc) {
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

        $router[] = new Route($prefix . '<aid [0-9]+>/<presenter>/[<action>/]', array(
                    'presenter' => array(
                        Route::VALUE => 'Event',
                        Route::FILTER_TABLE => array(
                            'ucastnici' => 'Participant',
                            'kniha' => 'Cashbook',
                    )),
                    'action' => "default",
                ));

        $router[] = new Route($prefix . '[<presenter>/][<action>/]', array(
                    'presenter' => 'Default',
                    'action' => 'default',
                ));
        return $router;
    }

}
