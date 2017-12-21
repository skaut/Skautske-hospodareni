<?php

namespace App;

use eGen\MessageBus\Bus\CommandBus;
use Model\UnitService;
use Model\UserService;
use Nette;
use Psr\Log\LoggerInterface;
use Skautis\Wsdl\AuthenticationException;
use WebLoader\Nette as WebLoader;

/**
 * @property-read Nette\Bridges\ApplicationLatte\Template $template
 */
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

    /** @var CommandBus */
    protected $commandBus;

    /** @var LoggerInterface */
    protected $logger;

    public function injectAll(
        WebLoader\LoaderFactory $webLoader,
        UserService $userService,
        UnitService $unitService,
        CommandBus $commandBus,
        LoggerInterface $logger
    ): void
    {
        $this->webLoader = $webLoader;
        $this->userService = $userService;
        $this->unitService = $unitService;
        $this->commandBus = $commandBus;
        $this->logger = $logger;
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

        \DependentSelectBox\JsonDependentSelectBox::tryJsonResponse($this /* (presenter) */);
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
        /** @var \Nette\Security\Identity $identity */
        $identity = $this->user->getIdentity();
        $identity->access = $this->userService->getAccessArrays($this->unitService);
    }

    /**
     * Returns OFFICIAL unit ID
     */
    public function getUnitId(): int
    {
        if($this->unitId === NULL) {
            $this->unitId = $this->unitService->getOficialUnit()->ID;
        }

        return $this->unitId;
    }

}
