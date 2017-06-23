<?php

namespace App;

use Model\UnitService;
use Model\UserService;
use Nette;
use Skautis\Wsdl\AuthenticationException;
use WebLoader\Nette as WebLoader;

abstract class BasePresenter extends Nette\Application\UI\Presenter
{

    /** @var UserService */
    protected $userService;

    /** @var UnitService */
    protected $unitService;
    
    /** @var string */
    private $wwwDir;

    /** @var string */
    private $appDir;

    /** @var int */
    private $unitId;

    /** @var WebLoader\LoaderFactory */
    private $webLoader;


    public function injectWebLoader(WebLoader\LoaderFactory $webLoader): void
    {
        $this->webLoader = $webLoader;
    }


    public function injectUserService(UserService $u): void
    {
        $this->userService = $u;
    }

    public function injectUnitService(UnitService $u): void
    {
        $this->unitService = $u;
    }

    protected function startup(): void
    {
        parent::startup();

        $this->wwwDir = $this->context->getParameters()["wwwDir"];
        $this->appDir = $this->context->getParameters()["appDir"];

        //adresář s částmi šablon pro použití ve více modulech
        $this->template->templateBlockDir = $this->appDir . "/templateBlocks/";

        $this->template->backlink = $backlink = $this->getParameter("backlink");
        if ($this->user->isLoggedIn() && $backlink !== NULL) {
            $this->restoreRequest($backlink);
        }

        try {
            if ($this->user->isLoggedIn()) { //prodluzuje přihlášení při každém požadavku
                $this->userService->isLoggedIn();
            }
        } catch (AuthenticationException $e) {
            if ($this->name != "Auth" || $this->params['action'] != "skautisLogout") { //pokud jde o odhlaseni, tak to nevadi
                throw $e;
            }
        }
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();
        if ($this->user->isLoggedIn()) {
            try {
                $this->template->myRoles = $this->userService->getAllSkautisRoles();
                $this->template->myRole = $this->userService->getRoleId();
            } catch (\Skautis\Wsdl\AuthenticationException $ex) {
                $this->user->logout(TRUE);
            } catch (\Skautis\Wsdl\WsdlException $ex) {
                if ($ex->getMessage() != "Could not connect to host") {
                    throw $ex;
                }
                $this->flashMessage("Nepodařilo se připojit ke Skautisu. Zkuste to prosím za chvíli nebo zkontrolujte, zda neprobíhá jeho údržba.");
                $this->redirect(":Default:");
            }
        }

        \DependentSelectBox\JsonDependentSelectBox::tryJsonResponse($this /* (presenter) */);
    }

    //změní přihlášenou roli ve skautISu
    public function handleChangeRole($roleId): void
    {
        $this->userService->updateSkautISRole($roleId);
        $this->updateUserAccess();
        $this->redirect("this");
    }

    protected function createComponentCss(): WebLoader\CssLoader
    {
        $control = $this->webLoader->createCssLoader('default');
        $control->setMedia('screen');

        return $control;
    }

    protected function createComponentJs(): WebLoader\JavaScriptLoader
    {
        return $this->webLoader->createJavaScriptLoader('default');
    }

    protected function updateUserAccess(): void
    {
        /* @var $identity \Nette\Security\Identity */
        $identity = $this->user->getIdentity();
        $identity->access = $this->userService->getAccessArrays($this->unitService);
    }

    /**
     * Returns OFFICIAL unit ID
     * @return int
     */
    protected function getUnitId(): int
    {
        if($this->unitId === NULL) {
            $this->unitId = $this->unitService->getOficialUnit()->ID;
        }

        return $this->unitId;
    }

}
