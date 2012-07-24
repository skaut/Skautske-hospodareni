<?php

/**
 * @author sinacek
 */
class Accountancy_BasePresenter extends BasePresenter {

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
     */
    protected $isEditable;
    
    /**
     * pole dostupných událostí
     * @var array
     */
    protected $availableActions;

    protected function startup() {
        parent::startup();

        if (!$this->user->isLoggedIn()) {
            $this->backlink = $this->storeRequest('+ 2 days');
            $this->redirect(":Default:", array("backlink" => $this->backlink));
        }
        
        if ($this->context->userService->isLoggedIn()) //prodluzuje přihlášení při každém požadavku
            $this->context->authService->updateLogoutTime();
    }

    function beforeRender() {
        parent::beforeRender();
        $this->template->myRoles = $this->context->userService->getAllSkautISRoles();
        $this->template->myRole = $this->context->userService->getRoleId();
        $this->template->registerHelperLoader("AccountancyHelpers::loader");
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
    static function createRoutes(RouteList $router, $prefix = "") {
        
        //$prefix .= "ucto/";
        
        $router[] = new Route($prefix . '<aid [0-9]+>/', array(
                    'module' => "Accountancy",
                    'presenter' => "Event",
                    'action' => "info",
                ));
        
        $router[] = new Route($prefix . '<aid [0-9]+>[/<presenter>][/<action>]', array(
                    'module' => "Accountancy",
                    'action' => "default",
                ));
        
        $router[] = new Route($prefix . '<module>/<presenter>/<action>', array(
                    'module' => "Accountancy",
                    'action' => 'default',
                ));
        
    }

}
