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
     * pole dostupných událostí pro akce
     * @var array
     */
    protected $availableEventActions;

    protected function startup() {
        parent::startup();

        if (!$this->user->isLoggedIn()) {
            $this->backlink = $this->storeRequest('+ 2 days');
            $this->redirect(":Default:", array("backlink" => $this->backlink));
        }
        
        if ($this->context->userService->isLoggedIn()) //prodluzuje přihlášení při každém požadavku
            $this->context->authService->updateLogoutTime();
        
        $this->template->aid = $this->aid = $this->getParameter("aid", NULL);
        
        if(isset($this->aid) && !is_null($this->aid)){//pokud je nastavene ID akce tak zjištuje stav dané akce a kontroluje oprávnění
            try {
                $event = $this->context->eventService->get($this->aid);
                $this->availableEventActions = $this->context->userService->actionVerify($this->aid);
                $this->template->isEditable = $this->isEditable = array_key_exists("EV_EventGeneral_UPDATE", $this->availableEventActions);
            } catch (SkautIS_PermissionException $exc) {
                $this->flashMessage($exc->getMessage(), "danger");
                $this->redirect("Event:");
            }   
        } else {
            $this->availableEventActions = $this->context->userService->actionVerify(NULL, NULL, "EV_EventGeneral"); //zjistení událostí nevázaných na konretnní akci
        }
    }

    function beforeRender() {
        parent::beforeRender();
        $this->template->myRoles = $this->context->userService->getAllSkautISRoles();
        $this->template->myRole = $this->context->userService->getRoleId();
        $this->template->registerHelperLoader("AccountancyHelpers::loader");
    }
    
    protected function editableOnly() {
        if ($this->isEditable) {
            $this->flashMessage("Akce je uzavřena a nelze ji upravovat.", "danger");
            if($this->isAjax()){
                $this->sendPayload();
            } else {
                $this->redirect("Event:");
            }
        }
    }

    /**
     * vytváří routy pro modul
     * @param RouteList $router
     * @param string $prefix 
     */
    static function createRoutes(RouteList $router, $prefix = "") {
        
        $prefix .= "ucto/";
        
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
