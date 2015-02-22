<?php

namespace App\AccountancyModule\TravelModule;

use Nette\Application\Routers\Route,
    Nette\Application\Routers\RouteList,
    Sinacek\MyRoute;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BasePresenter extends \App\AccountancyModule\BasePresenter {

    protected $unit;

    protected function startup() {
        parent::startup();
        $this->template->unit = $this->unit = $this->unitService->getOficialUnit();
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

        $router[] = new MyRoute($prefix . '<presenter>/[<action>/][<id>/]', array(
            'presenter' => array(
                Route::VALUE => 'Default',
                Route::FILTER_TABLE => array(
                    // řetězec v URL => presenter
                    'vozidla' => 'Vehicle',
                    'smlouvy' => 'Contract',
                )),
            'action' => 'default',
                ), Route::SECURED);
        return $router;
    }

}
