<?php

namespace App\AccountancyModule;

use Nette\Application\Routers\Route,
    Nette\Application\Routers\RouteList,
    Sinacek\MyRoute;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BasePresenter extends \App\BasePresenter {

    /**
     * backlink
     */
    protected $backlink;

    /**
     * id volane v url, vetsinou id akce
     * @var int
     */
    protected $aid;

    /**
     * je akci možné upravovat?
     * @var bool
     * @deprecated since version 2.0
     */
    protected $isEditable;

    /**
     * pole dostupných událostí s indexi podle SkautISu
     * @var array
     */
    protected $availableActions;
    
    public $isCamp;

    protected function startup() {
        parent::startup();

        if (!$this->user->isLoggedIn()) {
            $this->backlink = $this->storeRequest('+ 3 days');
            if ($this->isAjax()) {
                $this->forward(":Auth:ajax", array("backlink" => $this->backlink));
            } else {
                $this->redirect(":Default:", array("backlink" => $this->backlink));
            }
        }
    }

    function beforeRender() {
        parent::beforeRender();
        $this->template->registerHelperLoader("\App\AccountancyModule\AccountancyHelpers::loader");
    }

    protected function editableOnly() {
        throw new NotImplementedException("Implementují jednotlivé moduly");
//        if (!$this->isEditable) {
//            $this->flashMessage("Akce je uzavřena a nelze ji upravovat.", "danger");
//            if($this->isAjax()){
//                $this->sendPayload();
//            } else {
//                $this->redirect("Event:");
//            }
//        }
    }

    /**
     * vytváří routy pro modul
     * @param RouteList $router
     * @param string $prefix 
     */
    static function createRoutes($prefix = "") {
        $router = new RouteList("Accountancy");
//        $prefix .= "ucto/";
        $router[] = CampModule\BasePresenter::createRoutes();
        $router[] = EventModule\BasePresenter::createRoutes();
        $router[] = TravelModule\BasePresenter::createRoutes();

        $router[] = new MyRoute($prefix . '<module>/<presenter>[/<action>]', array(
            'module' => "Accountancy",
            'action' => 'default',
                ), Route::SECURED);

        return $router;
    }

    public function isAllowed($action, $avaibleActions = NULL) {
        $avaibleActions = $avaibleActions == NULL ? $this->availableActions : $avaibleActions;
        return array_key_exists($action, $avaibleActions);
    }

}
