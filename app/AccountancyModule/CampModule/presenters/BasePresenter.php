<?php

namespace AccountancyModule\CampModule;

use  Nette\Application\Routers\Route,
    Nette\Application\Routers\RouteList,
    Extras\Sinacek\MyRoute,
    SkautIS\Exception\PermissionException;

/**
 * @author sinacek
 */
class BasePresenter extends \AccountancyModule\BasePresenter {

    const STable = "EV_EventCamp";
    
    protected $camp;

    protected function startup() {
        parent::startup();

        $this->template->aid = $this->aid = $this->getParameter("aid", NULL);

        if (isset($this->aid) && !is_null($this->aid)) {//pokud je nastavene ID akce tak zjištuje stav dané akce a kontroluje oprávnění
            try {
                $this->template->camp = $this->camp = $this->context->campService->event->get($this->aid);
                $this->availableActions = $this->context->userService->actionVerify(self::STable, $this->aid);
                $this->template->isEditable = $this->isEditable = $this->isAllowed(self::STable . "_UPDATE_Real");
            } catch (PermissionException $exc) {
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
        $router = new RouteList("Camp");

        $prefix .= "tabory/";

        $router[] = new MyRoute($prefix . '<aid [0-9]+>/[<presenter>/][<action>/]', array(
                    'presenter' => array(
//                        Route::VALUE => 'Detail', //nefunguje pak report
                        Route::FILTER_TABLE => array(
                            'ucastnici' => 'Participant',
                            'kniha' => 'Cashbook',
                            'rozpocet' => 'Budget',
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
