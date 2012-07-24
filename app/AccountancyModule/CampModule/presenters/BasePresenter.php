<?php

/**
 * @author sinacek
 */
class Accountancy_Camp_BasePresenter extends Accountancy_BasePresenter {

    const STable = "EV_EventCamp";

    protected function startup() {
        parent::startup();

        $this->template->aid = $this->aid = $this->getParameter("aid", NULL);
        
        if(isset($this->aid) && !is_null($this->aid)){//pokud je nastavene ID akce tak zjištuje stav dané akce a kontroluje oprávnění
            try {
                $this->context->campService->event->get($this->aid);
                $this->availableActions = $this->context->userService->actionVerify(self::STable, $this->aid);
                $this->template->isEditable = $this->isEditable = array_key_exists(self::STable . "_UPDATE_Real", $this->availableActions);
            } catch (SkautIS_PermissionException $exc) {
                $this->flashMessage($exc->getMessage(), "danger");
                $this->redirect("Default:");
            }   
        } else {
            $this->availableActions = $this->context->userService->actionVerify(self::STable); //zjistení událostí nevázaných na konretnní
        }
    }
    
    protected function editableOnly() {
        if (!$this->isEditable) {
            $this->flashMessage("Akce je uzavřena a nelze ji upravovat.", "danger");
            if($this->isAjax()){
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
    static function createRoutes(RouteList $router, $prefix = "") {
        
        $prefix .= "tabory/";
        
//        $router[] = new Route($prefix . '<aid [0-9]+>/', array(
//                    'module' => "Accountancy",
//                    'presenter' => "Event",
//                    'action' => "info",
//                ));
//        
//        $router[] = new Route($prefix . '<aid [0-9]+>[/<presenter>][/<action>]', array(
//                    'module' => "Accountancy",
//                    'action' => "default",
//                ));
//        
//        $router[] = new Route($prefix . '<presenter>/<action>', array(
//                    'module' => "Accountancy",
//                    'presenter' => 'camp.default',
//                    'action' => 'default',
//                ));
        
    }

}
