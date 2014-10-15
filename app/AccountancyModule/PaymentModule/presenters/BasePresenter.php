<?php

namespace App\AccountancyModule\PaymentModule;

use Nette\Application\Routers\Route,
    Nette\Application\Routers\RouteList,
    Sinacek\MyRoute;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BasePresenter extends \App\AccountancyModule\BasePresenter {

    /** @persistent */
    public $aid;
    protected $object;
    protected $year;
    protected $isReadable;

    protected function startup() {
        parent::startup();
        $this->availableActions = $this->context->userService->actionVerify("OU_Unit", $this->aid);
//        $this->template->isEditable = $this->isEditable = array_key_exists("OU_Statement_INSERT_Unit", $this->availableActions); //moznost zalozit hospodárský výkaz
//        $this->template->isReadable = $this->isReadable = array_key_exists("OU_Statement_ALL_Unit", $this->availableActions);
//        if (!$this->isEditable) {
//            $this->flashMessage("Nemáte oprávnění pro zobrazení stránky", "warning");
//            $this->redirect(":Accountancy:Default:", array("aid" => NULL));
//        }
    }

    /**
     * vytváří routy pro modul
     * @param RouteList $router
     * @param string $prefix 
     */
    static function createRoutes($prefix = "") {
        $router = new RouteList("Payment");

        $prefix .= "platby/";

//        $router[] = new MyRoute($prefix . '<aid [0-9]+>/[<presenter>/][<action>/][<year>/]', array(
//            'presenter' => array(
//                Route::VALUE => 'Default',
//                Route::FILTER_TABLE => array(
//                    'kniha' => 'Cashbook',
//                    'paragony' => 'Chit',
//                    'rozpocet' => 'Budget',
//                )),
//            'action' => "default",
//                ), Route::SECURED);

        $router[] = new MyRoute($prefix . '[<presenter>/][<action>/]', array(
            'presenter' => 'Default',
            'action' => 'default',
                ), Route::SECURED);
        return $router;
    }

}
