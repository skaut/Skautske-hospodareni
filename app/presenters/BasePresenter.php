<?php

namespace App;

use App\AccountancyModule\Factories\FormFactory;
use App\Forms\BaseForm;
use Nette;
use Nette\Application\UI\Control;
use Skautis\Wsdl\AuthenticationException;
use WebLoader;
use WebLoader\Nette\CssLoader;
use WebLoader\Nette\JavaScriptLoader;

abstract class BasePresenter extends Nette\Application\UI\Presenter
{

    /**
     *
     * @var \Model\UserService
     */
    protected $userService;

    /**
     *
     * @var \Model\UnitService
     */
    protected $unitService;

    /** @var FormFactory */
    protected $formFactory;

    public function injectUserService(\Model\UserService $u): void
    {
        $this->userService = $u;
    }

    public function injectUnitService(\Model\UnitService $u): void
    {
        $this->unitService = $u;
    }

    public function injectFormFactory(FormFactory $formFactory): void
    {
        $this->formFactory = $formFactory;
    }

    protected function startup(): void
    {
        parent::startup();

        //adresář s částmi šablon pro použití ve více modulech
        $this->template->templateBlockDir = APP_DIR . "/templateBlocks/";

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
        $this->template->getLatte()->addFilter(NULL, "\App\AccountancyModule\AccountancyHelpers::loader");
        \DependentSelectBox\JsonDependentSelectBox::tryJsonResponse($this /* (presenter) */);
    }

    //změní přihlášenou roli ve skautISu
    public function handleChangeRole($roleId): void
    {
        $this->userService->updateSkautISRole($roleId);
        $this->updateUserAccess();
        $this->redirect("this");
    }

    /* @deprecated Use $this->formFactory directly */
    protected function prepareForm(Control $parent = NULL, string $name = NULL): BaseForm
    {
        $form = $this->formFactory->create();
        $this->addComponent($form, $name);
        return $form;
    }

    protected function createComponentCss(): CssLoader
    {
        $files = new WebLoader\FileCollection(WWW_DIR . '/css');
        $compiler = WebLoader\Compiler::createCssCompiler($files, WWW_DIR . '/webtemp');

        $control = new CssLoader($compiler, $this->context->getByType('Nette\Http\Request')->getUrl()->baseUrl . 'webtemp');
        $control->setMedia('screen');
        $files->addFiles([
            'fancybox/fancybox.css',
            'bootstrap.min.css',
            'bootstrap-select.css',
            'bootstrap-theme.min.css',
            'jquery-ui-1.10.0.custom.css',
            'bootstrap-datetimepicker.css',
            'typeaheadjs.css',
            'offline.css',
            'font-awesome.css',
            'datagrid.css',
            'datagrid-spinners.css',
            'site.css'
        ]);
        return $control;
    }

    protected function createComponentJs(): JavaScriptLoader
    {
        $files = new WebLoader\FileCollection(WWW_DIR . '/js');
        $compiler = WebLoader\Compiler::createJsCompiler($files, WWW_DIR . '/webtemp');
        $files->addFiles([
            'jquery-v1.11.1.js',
            'jquery-ui-1.10.0.custom.min.js',
            'bootstrap-datetimepicker.js',
            'bootstrap-datetimepicker.cs.js',
            'jquery.placeholder.min.js',//IE 8, 9 placeholders bugfix
            'bootstrap-select.js',
            'nette.ajax.js',
            'netteForms.js',
            'nextras.netteForms.js',
            'dependentselectbox.ajax.js',
            'jquery.nette.dependentselectbox.js',
            'bootstrap.js',
            'bootstrap3-typeahead.min.js',
            'jquery.fancybox.pack.js',
            'offline.js',
            'my.js',
            'nextras.datetimepicker.init.js',
            'ie10-viewport-bug-workaround.js', // IE10 viewport hack for Surface/desktop Windows 8 bug
            'datagrid.js',
            'datagrid-spinners.js',
        ]);
        return new JavaScriptLoader($compiler, $this->context->getByType('Nette\Http\Request')->getUrl()->baseUrl . 'webtemp');
    }

    protected function updateUserAccess(): void
    {
        $this->user->getIdentity()->access = $this->userService->getAccessArrays($this->unitService);
    }

}
