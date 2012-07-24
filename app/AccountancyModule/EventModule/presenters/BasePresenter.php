<?php

/**
 * @author sinacek
 */
class Accountancy_Event_BasePresenter extends Accountancy_BasePresenter {

    const STable = "EV_EventGeneral";

    protected function startup() {
        parent::startup();

        $this->template->aid = $this->aid = $this->getParameter("aid", NULL);
        
        if(isset($this->aid) && !is_null($this->aid)){//pokud je nastavene ID akce tak zjištuje stav dané akce a kontroluje oprávnění
            try {
                //$event = $this->context->eventService->event->get($this->aid);
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

    /**
     * vytváří routy pro modul
     * @param RouteList $router
     * @param string $prefix 
     */
    static function createRoutes(RouteList $router, $prefix = "") {
        
        $prefix .= "ucto/akce/";
        
        $router[] = new Route($prefix . '<aid [0-9]+>/', array(
                    'module' => "Accountancy",
                    'presenter' => "Event",
                    'action' => "info",
                ));
        
        $router[] = new Route($prefix . '<aid [0-9]+>[/<presenter>][/<action>]', array(
                    'module' => "Accountancy",
                    'action' => "default",
                ));
        
        $router[] = new Route($prefix . '<presenter>/<action>', array(
                    'module' => "Accountancy",
                    'presenter' => 'Event',
                    'action' => 'default',
                ));
        
    }

}
