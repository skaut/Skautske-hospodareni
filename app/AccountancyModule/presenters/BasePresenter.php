<?php

/**
 * @author sinacek
 */
class Accountancy_BasePresenter extends BasePresenter {

    protected $service;

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

        $sis = new SkautisService();
        if ($sis->isLoggedIn()) //prodluzuje přihlášení při každém načtení stránky
            $sis->updateLogoutTime();

        if (($aid = $this->context->httpRequest->getQuery("aid"))) {
            $this->template->aid = $this->aid = $aid;
        }
        
        if(isset($this->aid) && !is_null($this->aid)){//pokud je nastavene ID akce tak zjištuje stav dané akce
            $aservice = new ActionService();
            try {
                $this->template->actionState = $this->actionState = $aservice->get($this->aid)->ID_EventGeneralState;
            } catch (SkautIS_PermissionException $exc) {
                $this->flashMessage($exc->getMessage(), "danger");
                $this->redirect("Action:list");
            }   
        }
    }

    function beforeRender() {
        parent::beforeRender();
        $this->template->registerHelper('priceToString', 'AccountancyHelpers::priceToString');
        $this->template->registerHelper('price', 'AccountancyHelpers::price');
        
        $uservice = new UserService();
        $this->template->myRoles = $uservice->getAllSkautISRoles();
        $this->template->myRole = $uservice->getRoleId();
        $this->template->registerHelper('eventLabel', 'AccountancyHelpers::eventLabel');
//        $this->template->registerHelper('datNar', 'AccountancyHelpers::datNar');
    }

    public function handleChangeRole($id) {
        $uservice = new UserService();
        $uservice->updateSkautISRole($id);
        $this->redirect("this");
    }

    /**
     * tvoří routy pro modul
     * @param array $router
     * @param string $prefix 
     */
    static function createRoutes($router, $prefix ="") {

        $router[] = new Route($prefix . 'Ucetnictvi/p-<presenter>/a-<action>/', array(
                    'module' => "Accountancy",
//                    'presenter' => 'Default',
//                    'action' => 'default',
                ));
    }

}
