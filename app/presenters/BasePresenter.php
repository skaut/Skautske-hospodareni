<?php

namespace App;

use Nette,
    WebLoader,
    SkautIS\Exception\AuthenticationException,
    Nette\Forms\Container;

abstract class BasePresenter extends Nette\Application\UI\Presenter {

    protected function startup() {
        parent::startup();

        //adresář s částmi šablon pro použití ve více modulech
        $this->template->templateBlockDir = APP_DIR . "/templateBlocks/";

        $storage = $this->context->getByType('Nette\Http\Session')->getSection("__" . __CLASS__);
        $this->context->skautIS->setStorage($storage, TRUE);
        $this->template->backlink = $this->getParameter("backlink");
        $params = $this->context->getParameters();
        $this->template->ssl = $params['ssl'];

        Container::extensionMethod('addDatePicker', function (Container $container, $name, $label = NULL) {
            return $container[$name] = new \Nextras\Forms\Controls\DatePicker($label);
        });

        \DependentSelectBox\DependentSelectBox::register(); // First parameter of this method denotes name of method to add selectbox into form. Default name is addDependentSelectBox, but short name like addDSelect can be used.
        //\DependentSelectBox\JsonDependentSelectBox::register();

        \DependentSelectBox\JsonDependentSelectBox::register('addJSelect');



        try {
            if ($this->user->isLoggedIn() && $this->context->userService->isLoggedIn()) { //prodluzuje přihlášení při každém požadavku
                $this->context->authService->updateLogoutTime();
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
                $this->template->myRoles = $this->context->userService->getAllSkautISRoles();
                $this->template->myRole = $this->context->userService->getRoleId();
            } catch (\SkautIS\Exception\AuthenticationException $ex) {
                $this->user->logout();
            }
        }
        $this->template->getLatte()->addFilter(NULL, "\App\AccountancyModule\AccountancyHelpers::loader");
        \DependentSelectBox\JsonDependentSelectBox::tryJsonResponse($this /* (presenter) */);
    }

    //změní přihlášenou roli ve skautISu
    public function handleChangeRole($roleId) {
        $this->context->userService->updateSkautISRole($roleId);
        $this->redirect("this");
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
        $control = new WebLoader\Nette\CssLoader($compiler, $this->context->httpRequest->url->baseUrl . 'webtemp');
        $control->setMedia('screen');
        $files->addFiles(array(
            'fancybox/fancybox.css',
            'bootstrap.min.css',
            'bootstrap-responsive.min.css',
            'jquery-ui-1.10.0.custom.css',
            'bootstrap-datetimepicker.css',
            'my-responsive.css',
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
//            'jquery.touchwipe.min.js',
//            'mobile.js',
            //'my-datepicker.js',
            'combobox.js',
            //'jquery.nette.js',
            'nette.ajax.js',
            'netteForms.js',
            'nextras.netteForms.js',
            'dependentselectbox.ajax.js',
            'jquery.nette.dependentselectbox.js',
            'bootstrap.js',
//            'nextras.typeahead.init.js',
            'jquery.fancybox.pack.js',
//            'offline.js',
//            'html5.js',
//            'h5utils.js',
            'my.js',
            'nextras.datetimepicker.init.js',
        ));
        return new WebLoader\Nette\JavaScriptLoader($compiler, $this->context->httpRequest->url->baseUrl . 'webtemp');
    }

}
