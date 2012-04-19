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
     * stav akce
     * nastavená pouze pokud je nastaveno $this->aid
     * @var string (closed, draft, ...)
     */
    protected $actionState;

    protected function startup() {
        parent::startup();

        if (!$this->user->isLoggedIn()) {
            $this->backlink = $this->storeRequest();
            $this->redirect(":Default:", array("backlink" => $this->backlink));
        }
        
        if ($this->context->userService->isLoggedIn()) //prodluzuje přihlášení při každém požadavku
            $this->context->authService->updateLogoutTime();
        
        $this->template->aid = $this->aid = $this->getParameter("aid", NULL);
        
        if(isset($this->aid) && !is_null($this->aid)){//pokud je nastavene ID akce tak zjištuje stav dané akce a kontroluje oprávnění
            try {
                $this->template->actionState = $this->actionState = $this->context->eventService->get($this->aid)->ID_EventGeneralState;
            } catch (SkautIS_PermissionException $exc) {
                $this->flashMessage($exc->getMessage(), "danger");
                $this->redirect("Event:");
            }   
        }
    }

    function beforeRender() {
        parent::beforeRender();
        $this->template->registerHelperLoader("AccountancyHelpers::loader");
        $this->template->myRoles = $this->context->userService->getAllSkautISRoles();
        $this->template->myRole = $this->context->userService->getRoleId();
    }

    /**
     * vytváří routy pro modul
     * @param RouteList $router
     * @param string $prefix 
     */
    static function createRoutes(RouteList $router, $prefix = "") {
        
        $prefix .= "ucto/";
        
//        $router[] = new Route($prefix . '<aid [0-9]+>/', array(
//                    'module' => "Accountancy",
//                    'presenter' => "event",
//                    'action' => "info",
//                ));
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
