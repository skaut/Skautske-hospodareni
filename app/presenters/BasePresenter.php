<?php

namespace App;

use Nette,
    WebLoader,
    Skautis\Wsdl\AuthenticationException,
    Nette\Forms\Container;

abstract class BasePresenter extends Nette\Application\UI\Presenter {

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

    public function injectUserService(\Model\UserService $u) {
        $this->userService = $u;
    }

    public function injectUnitService(\Model\UnitService $u) {
        $this->unitService = $u;
    }

    protected function startup() {
        parent::startup();

        //adresář s částmi šablon pro použití ve více modulech
        $this->template->templateBlockDir = APP_DIR . "/templateBlocks/";

        $this->template->backlink = $backlink = $this->getParameter("backlink");
        if ($this->user->isLoggedIn() && $backlink !== NULL) {
            $this->restoreRequest($backlink);
        }

        Container::extensionMethod('addDatePicker', function (Container $container, $name, $label = NULL) {
            return $container[$name] = new \Nextras\Forms\Controls\DatePicker($label);
        });

        \DependentSelectBox\DependentSelectBox::register(); // First parameter of this method denotes name of method to add selectbox into form. Default name is addDependentSelectBox, but short name like addDSelect can be used.
        \DependentSelectBox\JsonDependentSelectBox::register('addJSelect');

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

    protected function beforeRender() {
        parent::beforeRender();
        if ($this->user->isLoggedIn()) {
            try {
                $this->template->myRoles = $this->userService->getAllSkautisRoles();
                $this->template->myRole = $this->userService->getRoleId();
            } catch (\Skautis\Wsdl\AuthenticationException $ex) {
                $this->user->logout(TRUE);
            } catch (\Skautis\Wsdl\WsdlException $ex) {
                if($ex->getMessage() != "Could not connect to host") {
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
    public function handleChangeRole($roleId) {
        $this->userService->updateSkautISRole($roleId);
        $this->updateUserAccess();
        $this->redirect("this");
    }

    protected function prepareForm($parent = NULL, $name = NULL) {
        $form = new Nette\Application\UI\Form($parent, $name);
        $form->setRenderer(new \Nextras\Forms\Rendering\Bs3FormRenderer());
        return $form;
    }

    public function createComponentCss() {
        $files = new WebLoader\FileCollection(WWW_DIR . '/css');
        $compiler = WebLoader\Compiler::createCssCompiler($files, WWW_DIR . '/webtemp');

        //s minimalizací zlobí bootstrap
//        $compiler->addFilter(new VariablesFilter(array('foo' => 'bar')));        
//        function mini($code) {
//            return CssMin::minify($code);
//        }
//        $compiler->addFilter("mini");
        $control = new WebLoader\Nette\CssLoader($compiler, $this->context->getByType('Nette\Http\Request')->getUrl()->baseUrl . 'webtemp');
        $control->setMedia('screen');
        $files->addFiles(array(
            'fancybox/fancybox.css',
            'bootstrap.min.css',
            'bootstrap-select.css',
            'bootstrap-theme.min.css',
            'jquery-ui-1.10.0.custom.css',
            'bootstrap-datetimepicker.css',
            'typeaheadjs.css',
//            'offline.css',
            'site.css'
        ));
        return $control;
    }

    public function createComponentJs() {
        $files = new WebLoader\FileCollection(WWW_DIR . '/js');
        $compiler = WebLoader\Compiler::createJsCompiler($files, WWW_DIR . '/webtemp');
        $files->addFiles(array(
            'jquery-v1.11.1.js',
            'jquery-ui-1.10.0.custom.min.js',
            'bootstrap-datetimepicker.js',
            'bootstrap-datetimepicker.cs.js',
            'jquery.placeholder.min.js',//IE 8, 9 placeholders bugfix
//            'jquery.touchwipe.min.js',
//            'mobile.js',
            //'my-datepicker.js',
            'bootstrap-select.js',
            //'jquery.nette.js',
            'nette.ajax.js',
            'netteForms.js',
            'nextras.netteForms.js',
            'dependentselectbox.ajax.js',
            'jquery.nette.dependentselectbox.js',
            'bootstrap.js',
            'bootstrap3-typeahead.min.js',
//            'nextras.typeahead.init.js',
            'jquery.fancybox.pack.js',
//            'offline.js',
//            'html5.js',
//            'h5utils.js',
            'my.js',
            'nextras.datetimepicker.init.js',
            'ie10-viewport-bug-workaround.js', // IE10 viewport hack for Surface/desktop Windows 8 bug
        ));
        return new WebLoader\Nette\JavaScriptLoader($compiler, $this->context->getByType('Nette\Http\Request')->getUrl()->baseUrl . 'webtemp');
    }

    protected function updateUserAccess() {
        $this->user->getIdentity()->access = $this->userService->getAccessArrays($this->unitService);
    }

}
