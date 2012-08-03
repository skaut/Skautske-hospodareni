<?php

/**
 * @author sinacek
 */
class Accountancy_Travel_BasePresenter extends Accountancy_BasePresenter {

    protected $unit;


    protected function startup() {
        parent::startup();
        
        $this->template->unit = $this->unit = $this->context->unitService->getOficialUnit();

//        $this->template->aid = $this->aid = $this->getParameter("aid", NULL);
//
//        if (isset($this->aid) && !is_null($this->aid)) {//pokud je nastavene ID akce tak zjištuje stav dané akce a kontroluje oprávnění
//            try {
//                //$event = $this->context->eventService->event->get($this->aid);
//                $this->availableActions = $this->context->userService->actionVerify(self::STable, $this->aid);
//                $this->template->isEditable = $this->isEditable = array_key_exists("EV_EventGeneral_UPDATE", $this->availableActions);
//            } catch (SkautIS_PermissionException $exc) {
//                $this->flashMessage($exc->getMessage(), "danger");
//                $this->redirect("Event:");
//            }
//        } else {
//            $this->availableActions = $this->context->userService->actionVerify(self::STable); //zjistení událostí nevázaných na konretnní akci
//        }
    }

    protected function editableOnly() {
        throw new NotImplementedException("todo");
//        if (!$this->isEditable) {
//            $this->flashMessage("Akce je uzavřena a nelze ji upravovat.", "danger");
//            if ($this->isAjax()) {
//                $this->sendPayload();
//            } else {
//                $this->redirect("Default:");
//            }
//        }
    }

    /**
     * vytváří routy pro modul
     * @param RouteList $router
     * @param string $prefix 
     */
    static function createRoutes($prefix = "") {
        $router = new RouteList("Travel");
        
        $prefix .= "cestaky/";

//        $router[] = new Route($prefix . '<aid [0-9]+>/<presenter>/[<action>/]', array(
//                    'presenter' => array(
//                        Route::VALUE => 'Event',
//                        Route::FILTER_TABLE => array(
//                            'ucastnici' => 'Participant',
//                            'kniha' => 'Cashbook',
//                    )),
//                    'action' => "default",
//                ));

        $router[] = new Route($prefix . '<presenter>/[<action>/]', array(
                    'presenter' => 'Default',
                    'action' => 'default',
                ));
        return $router;
    }

}
